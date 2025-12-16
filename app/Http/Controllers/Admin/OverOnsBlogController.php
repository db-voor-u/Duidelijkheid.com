<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OverOnsBlog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OverOnsBlogController extends Controller
{
    // Max file size: 12MB voor afbeelding/bestand, 50MB voor video
    private const MAX_IMAGE_KB = 12288;
    private const MAX_FILE_KB = 12288;
    private const MAX_VIDEO_KB = 51200;

    /** Overzicht: /hoofdbeheerder/over-ons-blog */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $type = $request->query('type');

        // 1. Basis query opbouwen (filter op type)
        $rootQuery = OverOnsBlog::query();

        if ($type && in_array($type, ['over_ons', 'innovatie'])) {
            $rootQuery->whereHas('category', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        // 2. Statistieken berekenen op basis van de huidige sectie (type)
        $total = (clone $rootQuery)->count();
        $published = (clone $rootQuery)->where('is_published', true)->count();
        $drafts = (clone $rootQuery)->where('is_published', false)->count();

        // 3. Search toepassen voor de lijstweergave
        $postsQuery = clone $rootQuery;
        if ($search !== '') {
            $postsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $posts = $postsQuery
            ->with('category')
            ->latest('published_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/pages/overonsblog/Index', [
            'stats' => [
                'total' => $total,
                'published' => $published,
                'drafts' => $drafts,
                'per_page' => $posts->perPage(),
            ],
            'posts' => $posts,
            'filters' => ['search' => $search, 'type' => $type],
        ]);
    }

    /** Nieuw blog formulier */
    public function create(Request $request)
    {
        $type = $request->query('section', 'over_ons');
        $validTypes = ['over_ons', 'innovatie', 'neurodiversiteit'];

        if (!in_array($type, $validTypes)) {
            $type = 'over_ons';
        }

        return Inertia::render('admin/pages/overonsblog/Form', [
            'post' => null,
            'section' => $type,
            'categories' => \App\Models\Category::where('type', $type)->select('id', 'name', 'color')->get(),
        ]);
    }

    /** Bewerk blog */
    public function edit(OverOnsBlog $post)
    {
        $post->load('category');
        // Bepaal sectie op basis van huidige categorie, of default naar over_ons
        $type = $post->category ? $post->category->type : 'over_ons';

        if (!in_array($type, ['over_ons', 'innovatie'])) {
            $type = 'over_ons';
        }

        return Inertia::render('admin/pages/overonsblog/Form', [
            'post' => $post->only([
                'id',
                'title',
                'slug',
                'excerpt',
                'content',
                'cover_image_path',
                'published_at',
                'is_published',
                'category_id',
                'meta_title',
                'meta_description',
                'canonical_url',
                'robots_index',
                'robots_follow',
                'og_image_path',
                'media_type',
                'video_path',
                'download_file_path',
            ]),
            'categories' => \App\Models\Category::where('type', $type)->select('id', 'name', 'color')->get(),
            'success' => session('success'),
        ]);
    }

    /** Opslaan nieuw blog */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // Unieke slug maken
        $slug = Str::slug($data['slug'] ?: $data['title']);
        $base = $slug;
        $i = 2;
        while (OverOnsBlog::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        $data['slug'] = $slug;

        // --- UPLOADS & MEDIA AFHANDELING ---
        $data['cover_image_path'] = null;
        $data['video_path'] = null;

        switch ($data['media_type']) {
            case 'image':
                if ($request->hasFile('cover_image')) {
                    $data['cover_image_path'] = $request->file('cover_image')->store('overons/covers', 'public');
                }
                break;
            case 'upload':
                if ($request->hasFile('video_upload')) {
                    $data['video_path'] = $request->file('video_upload')->store('overons/videos', 'public');
                }
                break;
            case 'youtube':
                $data['video_path'] = $data['youtube_url'] ?? null;
                break;
        }

        // OG Image
        if ($request->hasFile('og_image')) {
            $data['og_image_path'] = $request->file('og_image')->store('overons/og', 'public');
        }

        // DOWNLOAD FILE AFHANDELING
        if ($request->hasFile('download_file')) {
            $data['download_file_path'] = $request->file('download_file')->store('overons/downloads', 'public');
        } else {
            $data['download_file_path'] = null;
        }

        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        $data['robots_index'] = (bool) ($data['robots_index'] ?? true);
        $data['robots_follow'] = (bool) ($data['robots_follow'] ?? true);

        // Published_at auto-set als gepubliceerd maar geen datum
        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $data['category_id'] = $request->input('category_id');

        $post = OverOnsBlog::create($data);

        // FIX: Gebruik SLUG in plaats van ID
        return redirect()->route('admin.overons-blog.edit', ['post' => $post->slug])
            ->with('success', 'Over Ons Blog opgeslagen.');
    }

    /** Updaten bestaand blog */
    public function update(Request $request, OverOnsBlog $post)
    {
        $data = $this->validateData($request, $post->id);

        // Slug updaten
        $newSlug = $data['slug'] ?? $post->slug;
        if (!empty($newSlug) && $newSlug !== $post->slug) {
            $slug = Str::slug($newSlug);
            $base = $slug;
            $i = 2;
            while (OverOnsBlog::where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
                $slug = "{$base}-{$i}";
                $i++;
            }
            $data['slug'] = $slug;
        } else {
            $data['slug'] = $post->slug;
        }

        // --- MEDIA AFHANDELING BIJ UPDATE ---
        $currentCoverPath = $post->cover_image_path;
        $currentVideoPath = $post->video_path;

        $data['cover_image_path'] = $currentCoverPath;
        $data['video_path'] = $currentVideoPath;

        // OG Image
        if ($request->hasFile('og_image')) {
            if ($post->og_image_path) {
                Storage::disk('public')->delete($post->og_image_path);
            }
            $data['og_image_path'] = $request->file('og_image')->store('overons/og', 'public');
        }

        switch ($data['media_type']) {
            case 'image':
                if ($request->hasFile('cover_image')) {
                    if ($currentCoverPath) {
                        Storage::disk('public')->delete($currentCoverPath);
                    }
                    $data['cover_image_path'] = $request->file('cover_image')->store('overons/covers', 'public');
                }
                // Wis video
                if ($currentVideoPath && !Str::contains($currentVideoPath, ['youtube', 'youtu.be'])) {
                    Storage::disk('public')->delete($currentVideoPath);
                }
                $data['video_path'] = null;
                break;

            case 'upload':
                if ($request->hasFile('video_upload')) {
                    if ($currentVideoPath && !Str::contains($currentVideoPath, ['youtube', 'youtu.be'])) {
                        Storage::disk('public')->delete($currentVideoPath);
                    }
                    $data['video_path'] = $request->file('video_upload')->store('overons/videos', 'public');
                }
                // Wis afbeelding
                if ($currentCoverPath) {
                    Storage::disk('public')->delete($currentCoverPath);
                }
                $data['cover_image_path'] = null;
                break;

            case 'youtube':
                $data['video_path'] = $data['youtube_url'] ?? null;
                // Wis afbeelding en geüploade video
                if ($currentCoverPath) {
                    Storage::disk('public')->delete($currentCoverPath);
                }
                if ($currentVideoPath && !Str::contains($currentVideoPath, ['youtube', 'youtu.be'])) {
                    Storage::disk('public')->delete($currentVideoPath);
                }
                $data['cover_image_path'] = null;
                break;
        }

        // DOWNLOAD FILE AFHANDELING (UPDATE)
        $currentDownloadPath = $post->download_file_path;
        $data['download_file_path'] = $currentDownloadPath;

        if ($request->hasFile('download_file')) {
            if ($currentDownloadPath) {
                Storage::disk('public')->delete($currentDownloadPath);
            }
            $data['download_file_path'] = $request->file('download_file')->store('overons/downloads', 'public');
        } elseif ($request->input('download_file') === 'DELETE') {
            if ($currentDownloadPath) {
                Storage::disk('public')->delete($currentDownloadPath);
            }
            $data['download_file_path'] = null;
        }

        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        $data['robots_index'] = (bool) ($data['robots_index'] ?? true);
        $data['robots_follow'] = (bool) ($data['robots_follow'] ?? true);

        // Published_at auto-set als gepubliceerd maar geen datum
        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $data['category_id'] = $request->input('category_id');

        $post->update($data);

        // FIX: Gebruik fresh() om de NIEUWE slug op te halen als deze is gewijzigd
        return redirect()->route('admin.overons-blog.edit', ['post' => $post->fresh()->slug])
            ->with('success', 'Over Ons Blog bijgewerkt.');
    }

    /** Verwijderen */
    public function destroy(OverOnsBlog $post)
    {
        // Media verwijderen
        if ($post->cover_image_path) {
            Storage::disk('public')->delete($post->cover_image_path);
        }
        if ($post->video_path && !Str::contains($post->video_path, ['youtube', 'youtu.be'])) {
            Storage::disk('public')->delete($post->video_path);
        }
        if ($post->download_file_path) {
            Storage::disk('public')->delete($post->download_file_path);
        }
        if ($post->og_image_path) {
            Storage::disk('public')->delete($post->og_image_path);
        }

        $post->delete();

        return redirect()->route('admin.overons-blog.index')
            ->with('success', 'Over Ons Blog verwijderd.');
    }

    /** Validatie (gebruikt voor Store en Update) */
    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('over_ons_blogs', 'slug')->ignore($ignoreId)
            ],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'content' => ['required'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots_index' => ['sometimes', 'boolean'],
            'robots_follow' => ['sometimes', 'boolean'],

            // MEDIA VELDEN
            'media_type' => ['required', Rule::in(['image', 'youtube', 'upload'])],
            'youtube_url' => ['nullable', 'url'],
            'cover_image' => ['nullable', 'image', 'max:' . self::MAX_IMAGE_KB],
            'video_upload' => ['nullable', 'file', 'mimes:mp4,webm,ogg', 'max:' . self::MAX_VIDEO_KB],
            'og_image' => ['nullable', 'image', 'max:' . self::MAX_IMAGE_KB],
            'category_id' => ['nullable', 'exists:categories,id'],
        ];

        $messages = [
            'required' => 'Dit veld is verplicht.',
            'string' => 'Dit veld moet tekst zijn.',
            'max' => [
                'string' => 'Mag niet meer dan :max tekens bevatten.',
                'file' => 'Bestand mag niet groter zijn dan :max kilobytes.',
            ],
            'unique' => 'Deze waarde is al in gebruik.',
            'date' => 'Dit moet een geldige datum zijn.',
            'boolean' => 'Dit veld moet waar of onwaar zijn.',
            'url' => 'Dit moet een geldige URL zijn.',
            'required_if' => 'Dit veld is verplicht wanneer :other :value is.',
            'image' => 'Dit moet een afbeelding zijn.',
            'file' => 'Dit moet een bestand zijn.',
            'mimes' => 'Bestandstype moet zijn: :values.',
            'exists' => 'De geselecteerde waarde is ongeldig.',
            'array' => 'Dit moet een lijst zijn.',
            'download_file.file' => 'Het downloadbestand moet een geldig bestand zijn.',
            'title.required' => 'Titel is verplicht.',
            'content.required' => 'Inhoud is verplicht.',
            'youtube_url.url' => 'YouTube URL moet een geldige URL zijn.',
            'video_upload.mimes' => 'Het videobestand moet een MP4, WebM, of Ogg-bestand zijn.',
        ];

        /* Custom validation for download_file */
        if ($request->hasFile('download_file')) {
            $rules['download_file'] = ['file', 'mimes:pdf,doc,docx,zip', 'max:' . self::MAX_FILE_KB];
        } else {
            $rules['download_file'] = [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_string($value) && $value === 'DELETE')
                        return;
                    if (!is_null($value))
                        $fail('Het bestand moet geldig zijn.');
                }
            ];
        }

        return $request->validate($rules, $messages);
    }
}
