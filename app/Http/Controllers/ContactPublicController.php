<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageNotification;
use App\Models\ContactMessage;
use App\Models\ContactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ContactPublicController extends Controller
{
    public function show()
    {
        // ... (code voor show blijft hetzelfde) ...
        $page = null;

        if (Schema::hasTable('contact_pages')) {
            $page = ContactPage::where('published', true)->first() ?? ContactPage::first();
        }

        $payload = [
            'hero_title' => $page?->hero_title ?? 'Contact',
            'hero_subtitle' => $page?->hero_subtitle ?? null,
            'intro' => $page?->intro ?? 'Stel je vraag, we reageren snel.',
            'hero_image_path' => $page?->hero_image_path ?? null,
            'show_form' => $page?->show_form ?? true,
            'seo_title' => $page?->seo_title ?? 'Contact',
            'seo_description' => $page?->seo_description ?? null,
            'published' => $page?->published ?? true,
            'canonical_url' => $page?->canonical_url ?? null,
            'robots_index' => $page?->robots_index ?? true,
            'robots_follow' => $page?->robots_follow ?? true,
        ];

        session()->put('contact_rendered_at_ms', (int) round(microtime(true) * 1000));

        return Inertia::render('Contact', ['page' => $payload]);
    }

    public function submit(Request $request)
    {
        // 1. Validatie
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:160',
                function ($attribute, $value, $fail) {
                    $blockedTlds = ['.ru', '.cn', '.xyz'];
                    foreach ($blockedTlds as $tld) {
                        if (str_ends_with(strtolower($value), $tld)) {
                            $fail("E-mailadressen eindigend op {$tld} worden niet geaccepteerd.");
                        }
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],

            // DIT IS DE REGEL DIE DE CHECKBOX VERPLICHT:
            'consent' => ['required', 'accepted'],

            'company' => ['nullable', 'string', 'max:0'],
            'duration_ms' => ['required', 'numeric', 'min:2000'],
            'referer' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Naam is verplicht.',
            'email.required' => 'E-mail is verplicht.',
            'subject.required' => 'Onderwerp is verplicht.',
            'message.required' => 'Bericht is verplicht.',
            'consent.accepted' => 'Je moet akkoord gaan met het privacybeleid om te versturen.',
            'company.max' => 'Spam-detectie mislukt (H).',
            'duration_ms.min' => 'Spam-detectie mislukt (T).',
        ]);

        // ... (rest van de logica blijft hetzelfde) ...
        $isSpam = false;
        $spamReason = null;

        if (!empty($data['company'])) {
            $isSpam = true;
            $spamReason = 'Honeypot (company) veld ingevuld.';
        } elseif ((int) $data['duration_ms'] < 2000) {
            $isSpam = true;
            $spamReason = 'Te snel ingevuld (' . $data['duration_ms'] . ' ms)';
        }

        $dedupe = hash(
            'sha256',
            mb_strtolower($data['email']) . '|' .
            mb_strtolower($data['subject']) . '|' .
            preg_replace('/\s+/u', ' ', trim($data['message']))
        );

        // 3. Opslaan
        $message = ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'consent' => true, // We weten zeker dat het 'true' is door de validatie
            'status' => 'new',
            'is_spam' => $isSpam,
            'spam_reason' => $spamReason,
            'hp_filled' => !empty($data['company']),
            'duration_ms' => $data['duration_ms'],
            'dedupe_hash' => $dedupe,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'referer' => $data['referer'] ?? null,
            'locale' => app()->getLocale() ?: 'nl',
            'source' => 'website',
        ]);

        // 4. Verstuur de admin notificatie e-mail
        if (!$isSpam) {
            try {
                $notificationEmail = env('CONTACT_NOTIFICATION_EMAIL', config('mail.from.address'));
                Mail::to($notificationEmail)->send(new ContactMessageNotification($message));
            } catch (\Exception $e) {
                Log::error('Fout bij versturen contact notificatie: ' . $e->getMessage());
            }
        }

        return Redirect::back();
    }
}
