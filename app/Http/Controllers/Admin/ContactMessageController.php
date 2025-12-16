<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

// Imports toegevoegd voor Mail, Log en de specifieke Exceptions
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminContactReply;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\UniqueConstraintViolationException;

class ContactMessageController extends Controller
{
    /**
     * Toont het overzicht (inbox) van contactberichten.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $status = $request->get('status');
        $spam = $request->boolean('spam', false);

        $q = ContactMessage::query();

        $driver = DB::getDriverName();
        $like = $driver === 'pgsql' ? 'ilike' : 'like';

        if ($search !== '') {
            $q->where(function ($qq) use ($search, $like) {
                $qq->where('name', $like, "%{$search}%")
                    ->orWhere('email', $like, "%{$search}%")
                    ->orWhere('subject', $like, "%{$search}%")
                    ->orWhere('message', $like, "%{$search}%");
            });
        }

        if ($status) {
            if ($status === 'archived') {
                $q->whereNotNull('archived_at');
            } else {
                $q->where('status', $status)->whereNull('archived_at');
            }
        }

        if ($spam) {
            $q->where('is_spam', true);
        }

        $messages = $q->orderByDesc('created_at')
            ->orderByDesc('id')
            // Velden toegevoegd die Index.vue nodig heeft
            ->select('id', 'name', 'email', 'subject', 'status', 'created_at', 'is_spam', 'duration_ms', 'message', 'attachment')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'subject' => $m->subject,
                'status' => $m->status,
                'is_spam' => $m->is_spam,
                'duration_ms' => $m->duration_ms,
                'created_at' => $m->created_at?->toIso8601String(),
                'message' => $m->message, // voor de 'short()' helper
                'has_attachment' => !empty($m->attachment),
            ]);

        $stats = [
            'total' => ContactMessage::count(),
            'new' => ContactMessage::where('status', 'new')->whereNull('archived_at')->count(),
            'read' => ContactMessage::where('status', 'read')->whereNull('archived_at')->count(),
            'replied' => ContactMessage::where('status', 'replied')->whereNull('archived_at')->count(),
            'closed' => ContactMessage::where('status', 'closed')->whereNull('archived_at')->count(),
            'archived' => ContactMessage::whereNotNull('archived_at')->count(),
            'spam' => ContactMessage::where('is_spam', true)->count(),
        ];

        return Inertia::render('admin/pages/contact/Index', [
            'messages' => $messages,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'spam' => $spam,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Toont het "Nieuw bericht opstellen" formulier (Create.vue).
     */
    public function create(Request $request)
    {
        return Inertia::render('admin/pages/contact/Create', [
            'prefill' => [
                'email' => $request->query('email'),
                'name' => $request->query('name'),
                'subject' => $request->query('subject'),
                'body' => $request->query('body'),
            ]
        ]);
    }

    /**
     * Slaat een nieuw, door de admin opgesteld, bericht op.
     */
    public function store(Request $request)
    {
        // Valideer de velden die *echt* uit het Create.vue formulier komen
        $data = $request->validate([
            'to_name' => ['nullable', 'string', 'max:120'],
            'to_email' => ['required', 'email', 'max:160'],
            'cc' => ['nullable', 'string', 'max:255'],
            'bcc' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string'], // Dit is de Quill HTML
        ]);

        $dedupe = hash(
            'sha256',
            mb_strtolower($data['to_email']) . '|' .
            mb_strtolower($data['subject']) . '|' .
            preg_replace('/\s+/u', ' ', trim(strip_tags($data['body'])))
        );

        // Vang de "duplicate" error af
        try {
            ContactMessage::create([
                // Map de formulier velden naar de database kolommen
                'name' => $data['to_name'] ?? $data['to_email'],
                'email' => $data['to_email'],
                'phone' => null,
                'subject' => $data['subject'],
                'message' => $data['body'], // Sla de Quill HTML op
                'consent' => false,
                'status' => 'replied',
                'replied_at' => now(),
                'handled_by' => optional(auth('admin'))->id(),
                'is_spam' => false,
                'dedupe_hash' => $dedupe,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'referer' => substr((string) $request->headers->get('referer'), 0, 255),
                'locale' => app()->getLocale() ?: 'nl',
                'source' => 'admin',
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Vang de "duplicate key" fout (SQLSTATE 23505) op
            return Redirect::back()->withErrors(['message' => 'Dit identieke bericht is al (recent) verstuurd.']);
        }

        // Split CC en BCC e-mails
        $cc = $this->splitEmails($data['cc'] ?? '');
        $bcc = $this->splitEmails($data['bcc'] ?? '');

        // Verstuur de e-mail
        try {
            Mail::to($data['to_email'])
                ->cc($cc)
                ->bcc($bcc)
                ->send(new AdminContactReply($data['subject'], $data['body']));
        } catch (\Exception $e) {
            Log::error('Fout bij versturen admin antwoord: ' . $e->getMessage());
            // Keer terug met een foutmelding (dit is waarschijnlijk je .env probleem)
            return Redirect::back()->withErrors(['message' => 'E-mail kon niet worden verzonden, maar bericht is wel opgeslagen. Controleer je .env instellingen.']);
        }

        // Gebruik het pad, want de route-naam gaf cache-problemen
        return Redirect::to('/hoofdbeheerder/contact')->with('success', 'Bericht verzonden en geregistreerd.');
    }

    /**
     * Toont één specifiek bericht.
     */
    public function show(ContactMessage $message)
    {
        if (is_null($message->read_at)) {
            $message->read_at = now();
            $message->status = $message->status === 'new' ? 'read' : $message->status;
            $message->handled_by = $message->handled_by ?: optional(auth('admin'))->id();
            $message->save();
        }

        return Inertia::render('admin/pages/contact/Show', [
            'message' => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone,
                'subject' => $message->subject,
                'message' => $message->message,
                'status' => $message->status,
                'is_spam' => (bool) $message->is_spam,
                'spam_reason' => $message->spam_reason,
                'read_at' => $message->read_at?->toIso8601String(),
                'replied_at' => $message->replied_at?->toIso8601String(),
                'archived_at' => $message->archived_at?->toIso8601String(),
                'created_at' => $message->created_at?->toIso8601String(),
                'ip' => $message->ip,
                'user_agent' => $message->user_agent,
                'referer' => $message->referer,
            ],
        ]);
    }

    /**
     * Slaat een antwoord op een bericht op (vanuit de 'reply' modal in Index.vue).
     */
    public function reply(Request $request, ContactMessage $message)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'subject' => ['required', 'string', 'max:160'],
            'cc' => ['nullable', 'string'],
            'bcc' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ], [
            'attachment.max' => 'Bijlage mag niet groter zijn dan 10MB.',
        ]);

        $cc = $this->splitEmails($data['cc'] ?? '');
        $bcc = $this->splitEmails($data['bcc'] ?? '');
        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $attachmentName = $request->file('attachment')->getClientOriginalName();
            $pathStr = $request->file('attachment')->store('attachments'); // Store returns relative path
            $fullPath = Storage::path($pathStr); // Gebruik storage facade voor correcte 'root' (bv app/private)

            if (file_exists($fullPath)) {
                $attachmentPath = $fullPath;
            } else {
                Log::warning("Attachment opgeslagen maar niet gevonden op pad: $fullPath");
                // We gaan door zonder bijlage om crash te voorkomen, of we returnen error
            }
        }

        try {
            Mail::to($message->email)
                ->cc($cc)
                ->bcc($bcc)
                ->send(new AdminContactReply($data['subject'], $data['body'], $attachmentPath, $attachmentName));
        } catch (\Exception $e) {
            Log::error('Fout bij versturen admin antwoord: ' . $e->getMessage());
            return Redirect::back()->withErrors(['message' => 'Fout bij versturen antwoord: ' . $e->getMessage()]);
        }

        // 1. Update status van het originele bericht
        $message->status = 'replied';
        $message->replied_at = now();
        $message->handled_by = optional(auth('admin'))->id();
        $message->save();

        // 2. Sla het ANTWOORD op als nieuw bericht zodat het in de geschiedenis verschijnt
        ContactMessage::create([
            'name' => config('app.name', 'Admin') . ' (Admin)', // Dynamisch app naam
            'email' => $message->email, // Zelfde email zodat het in history komt
            'subject' => $data['subject'],
            'message' => $data['body'],
            'attachment' => $attachmentPath ? $pathStr : null, // Sla pad op
            'status' => 'replied',
            'source' => 'admin-reply', // Herkenbaar als uitgaand
            'is_spam' => false,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
            'handled_by' => optional(auth('admin'))->id(),
            // Dedupe hash om errors te voorkomen, random uniek maken voor uitgaand
            'dedupe_hash' => hash('sha256', 'reply-' . uniqid() . now()->timestamp),
            'consent' => true,
            'hp_filled' => false,
        ]);

        return Redirect::back()->with('success', 'Antwoord opgeslagen.');
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:160'],   // Naam ontvanger (optioneel)
            'email' => ['required', 'email', 'max:160'],    // E-mail ontvanger
            'cc' => ['nullable', 'string'],             // komma/; gescheiden
            'bcc' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:20000'], // HTML uit je editor
            'attachment' => ['nullable', 'file', 'max:10240'], // Max 10MB
        ], [
            'email.required' => 'E-mail ontvanger is verplicht.',
            'subject.required' => 'Onderwerp is verplicht.',
            'body.required' => 'Bericht is verplicht.',
            'attachment.max' => 'Bijlage mag niet groter zijn dan 10MB.',
        ]);

        $cc = $this->splitEmails($data['cc'] ?? '');
        $bcc = $this->splitEmails($data['bcc'] ?? '');
        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $attachmentName = $request->file('attachment')->getClientOriginalName();
            $pathStr = $request->file('attachment')->store('attachments');
            $fullPath = Storage::path($pathStr);

            if (file_exists($fullPath)) {
                $attachmentPath = $fullPath;
            } else {
                Log::warning("Attachment opgeslagen maar niet gevonden op pad: $fullPath");
            }
        }

        // Versturen
        try {
            Mail::to($data['email'], $data['name'] ?? null)
                ->cc($cc)
                ->bcc($bcc)
                ->send(new AdminContactReply($data['subject'], $data['body'], $attachmentPath, $attachmentName));

        } catch (\Throwable $e) {
            Log::warning('Admin contact send failed: ' . $e->getMessage(), ['exception' => $e]);
            return back()->withErrors(['message' => 'Fout bij versturen: ' . $e->getMessage()])->withInput();
        }

        // Log/opslaan als uitgaand bericht
        $msg = ContactMessage::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => strip_tags($data['body']), // plain tekstversie
            'consent' => true,
            'status' => 'replied',
            'handled_by' => auth('admin')->id(),
            'hp_filled' => false,
            'duration_ms' => 0,
            'is_spam' => false,
            'dedupe_hash' => hash('sha256', strtolower($data['email']) . '|' . $data['subject'] . '|' . now()->format('YmdHis')),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'referer' => substr((string) $request->headers->get('referer'), 0, 255),
            'utm' => null,
            'locale' => app()->getLocale() ?: 'nl',
            'source' => 'admin-send',
            'user_id' => null,
        ]);

        return redirect()->route('admin.contact.show', $msg->id)->with('success', 'Bericht verzonden.');
    }

    private function splitEmails(string $value): array
    {
        $parts = preg_split('/[,\s;]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_values(array_filter($parts, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
    }

    /**
     * Werkt de status bij (archiveren, spam, etc.) vanuit Index.vue.
     */
    public function updateStatus(Request $request, ContactMessage $message)
    {
        $action = $request->validate([
            'action' => ['required', 'in:read,replied,closed,archive,unarchive,spam,ham']
        ])['action'];

        $now = now();

        switch ($action) {
            case 'read':
                $message->status = 'read';
                $message->read_at = $message->read_at ?: $now;
                $message->handled_by = $message->handled_by ?: optional(auth('admin'))->id();
                break;

            case 'replied':
                $message->status = 'replied';
                $message->replied_at = $now;
                $message->handled_by = optional(auth('admin'))->id();
                break;

            case 'closed':
                $message->status = 'closed';
                $message->handled_by = optional(auth('admin'))->id();
                break;

            case 'archive':
                $message->archived_at = $now;
                break;

            case 'unarchive':
                $message->archived_at = null;
                break;

            case 'spam':
                $message->is_spam = true;
                $message->spam_reason = $message->spam_reason ?? 'Handmatig gemarkeerd';
                break;

            case 'ham': // Tegenovergestelde van spam
                $message->is_spam = false;
                $message->spam_reason = null;
                break;


        }

        $message->save();

        return Redirect::back()->with('success', 'Status bijgewerkt.');
    }

    /**
     * Verwijdert een bericht (soft delete).
     */
    public function destroy(ContactMessage $message)
    {
        $message->delete();
        return Redirect::back()->with('success', 'Bericht verwijderd.');
    }

    /**
     * Herstelt een soft-deleted bericht.
     */
    public function restore(int $id)
    {
        $message = ContactMessage::withTrashed()->findOrFail($id);
        if ($message->trashed()) {
            $message->restore();
        }
        return Redirect::back()->with('success', 'Bericht hersteld.');
    }

    /**
     * Haalt eerdere berichten op van dezelfde afzender (email).
     */
    public function history(ContactMessage $message)
    {
        $history = ContactMessage::where('email', $message->email)
            ->where('id', '!=', $message->id)
            ->orderByDesc('created_at')
            ->select('id', 'name', 'subject', 'message', 'status', 'created_at', 'is_spam', 'source', 'attachment')
            ->take(10) // Laatste 10 is vaak genoeg
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'subject' => $m->subject,
                'message' => $m->message, // Voor snippet
                'status' => $m->status,
                'created_at' => $m->created_at?->toIso8601String(),
                'is_spam' => $m->is_spam,
                'source' => $m->source,
                'has_attachment' => !empty($m->attachment),
            ]);
        return response()->json($history);
    }
}
