<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BlogController extends Controller
{
    private const MAX_IMAGE_KB = 12288;
    private const MAX_FILE_KB = 12288;
    private const MAX_VIDEO_KB = 51200;

    // ... index en create methoden blijven hetzelfde ...
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $query = Blog::query();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
            });
        }
        $posts = $query->with('category')->latest('published_at')->latest('id')->paginate(10)->withQueryString();
        return Inertia::render('admin/pages/blog/Index', [
            'stats' => [
                'total' => Blog::count(),
                'published' => Blog::where('is_published', true)->count(),
                'drafts' => Blog::where('is_published', false)->count(),
                'per_page' => $posts->perPage(),
            ],
            'posts' => $posts,
            'filters' => ['search' => $search],
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/pages/blog/Form', [
            'post' => null,
            'categories' => \App\Models\Category::whereNotIn('type', ['innovatie', 'over_ons'])->select('id', 'name', 'color')->get()
        ]);
    }

    public function edit(Blog $post)
    {
        return Inertia::render('admin/pages/blog/Form', [
            'post' => $post->only([
                'id',
                'title',
                'slug',
                'excerpt',
                'content',
                'cover_image_path',
                'published_at',
                'is_published',
                'meta_title',
                'meta_description',
                'canonical_url',
                'robots_index',
                'robots_follow',
                'og_image_path',
                'media_type',
                'video_path',
                'download_file_path',
                // 👇 NIEUW: Stuur de bestaande extra bestanden mee
                'extra_files_paths',
                'category_id', // 👇 Categorie ID
            ]),
            'categories' => \App\Models\Category::whereNotIn('type', ['innovatie', 'over_ons'])->select('id', 'name', 'color')->get(),
            'success' => session('success'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $slug = Str::slug($data['slug'] ?: $data['title']);
        $base = $slug;
        $i = 2;
        while (Blog::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        $data['slug'] = $slug;

        // ... Media logica (hetzelfde als voorheen) ...
        $data['cover_image_path'] = null;
        $data['video_path'] = null;
        switch ($data['media_type']) {
            case 'image':
                if ($request->hasFile('cover_image'))
                    $data['cover_image_path'] = $request->file('cover_image')->store('blog/covers', 'public');
                break;
            case 'upload':
                if ($request->hasFile('video_upload'))
                    $data['video_path'] = $request->file('video_upload')->store('blog/videos', 'public');
                break;
            case 'youtube':
                $data['video_path'] = $data['youtube_url'] ?? null;
                break;
        }

        if ($request->hasFile('download_file'))
            $data['download_file_path'] = $this->storeWithOriginalName($request->file('download_file'), 'blog/downloads');
        else
            $data['download_file_path'] = null;

        // 👇 NIEUW: Extra bestanden (array)
        $extraPaths = [];
        if ($request->hasFile('extra_files')) {
            foreach ($request->file('extra_files') as $file) {
                // Maximaal 3 bestanden totaal bij aanmaken
                if (count($extraPaths) < 3) {
                    $extraPaths[] = $this->storeWithOriginalName($file, 'blog/downloads');
                }
            }
        }
        $data['extra_files_paths'] = $extraPaths;

        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        $data['robots_index'] = (bool) ($data['robots_index'] ?? true);
        $data['robots_follow'] = (bool) ($data['robots_follow'] ?? true);

        $post = Blog::create($data);

        return redirect()->route('admin.blog.edit', $post->slug)->with('success', 'Blog opgeslagen.');
    }

    public function update(Request $request, Blog $post)
    {
        $data = $this->validateData($request, $post->id);

        // Slug logic
        if (!empty($data['slug']) && $data['slug'] !== $post->slug) {
            $slug = Str::slug($data['slug']);
            $base = $slug;
            $i = 2;
            while (Blog::where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
                $slug = "{$base}-{$i}";
                $i++;
            }
            $data['slug'] = $slug;
        } else {
            $data['slug'] = $post->slug;
        }

        // ... Media logica (verkort weergegeven, neem over uit je vorige versie) ...
        $data['cover_image_path'] = $post->cover_image_path;
        $data['video_path'] = $post->video_path;
        // (Hier moet je volledige switch case voor media_type staan zoals in je huidige controller)
        switch ($data['media_type']) {
            case 'image':
                if ($request->hasFile('cover_image')) {
                    if ($post->cover_image_path)
                        Storage::disk('public')->delete($post->cover_image_path);
                    $data['cover_image_path'] = $request->file('cover_image')->store('blog/covers', 'public');
                } else if (empty($data['cover_image_path_keep'])) {
                    if ($post->cover_image_path)
                        Storage::disk('public')->delete($post->cover_image_path);
                    $data['cover_image_path'] = null;
                }
                break;
            // ... voeg hier je youtube/upload cases toe ...
            case 'upload':
                if ($request->hasFile('video_upload')) {
                    if ($post->video_path && !Str::contains($post->video_path, ['youtube', 'youtu.be']))
                        Storage::disk('public')->delete($post->video_path);
                    $data['video_path'] = $request->file('video_upload')->store('blog/videos', 'public');
                } else if (empty($data['video_path_keep'])) {
                    if ($post->video_path && !Str::contains($post->video_path, ['youtube', 'youtu.be']))
                        Storage::disk('public')->delete($post->video_path);
                    $data['video_path'] = null;
                }
                break;
            case 'youtube':
                $data['video_path'] = $data['youtube_url'] ?? null;
                break;
        }

        // Main download file logic
        $data['download_file_path'] = $post->download_file_path;
        if ($request->hasFile('download_file')) {
            if ($post->download_file_path)
                Storage::disk('public')->delete($post->download_file_path);
            $data['download_file_path'] = $this->storeWithOriginalName($request->file('download_file'), 'blog/downloads');
        } else if (isset($data['download_file']) && $data['download_file'] === 'DELETE') {
            if ($post->download_file_path)
                Storage::disk('public')->delete($post->download_file_path);
            $data['download_file_path'] = null;
        }

        // 👇 NIEUW: Extra bestanden logica
        // 1. Start met wat we al hebben
        $currentExtraPaths = $post->extra_files_paths ?? [];

        // 2. Verwijder bestanden die de frontend heeft gemarkeerd
        if ($request->has('remove_extra_files')) {
            $filesToRemove = $request->input('remove_extra_files'); // Array van strings
            if (is_array($filesToRemove)) {
                foreach ($filesToRemove as $path) {
                    // Veiligheidscheck: verwijder alleen als het echt in onze lijst stond
                    if (in_array($path, $currentExtraPaths)) {
                        if (Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                        }
                        // Verwijder uit de array
                        $currentExtraPaths = array_diff($currentExtraPaths, [$path]);
                    }
                }
            }
        }

        // 3. Voeg nieuwe uploads toe
        if ($request->hasFile('extra_files')) {
            foreach ($request->file('extra_files') as $file) {
                // Check limiet: Huidig overgebleven + Nieuwe mag max 3 zijn
                if (count($currentExtraPaths) < 3) {
                    $currentExtraPaths[] = $this->storeWithOriginalName($file, 'blog/downloads');
                }
            }
        }

        // Re-index array (zodat het [0, 1] wordt en niet [0, 2]) en opslaan
        $data['extra_files_paths'] = array_values($currentExtraPaths);


        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        $data['robots_index'] = (bool) ($data['robots_index'] ?? true);
        $data['robots_follow'] = (bool) ($data['robots_follow'] ?? true);

        $post->update($data);

        return back()->with('success', 'Blog bijgewerkt.');
    }

    private function storeWithOriginalName($file, $directory)
    {
        $originalName = $file->getClientOriginalName();
        // Sanitize filename: remove special chars, spaces to dashes, etc.
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $extension = $file->getClientOriginalExtension();

        // Add timestamp to prevent duplicates
        $fileName = time() . '_' . $safeName . '.' . $extension;

        return $file->storeAs($directory, $fileName, 'public');
    }

    public function destroy(Blog $post)
    {
        // ... (bestaande deletes voor cover/video/download) ...
        if ($post->cover_image_path)
            Storage::disk('public')->delete($post->cover_image_path);
        if ($post->download_file_path)
            Storage::disk('public')->delete($post->download_file_path);

        // 👇 NIEUW: Verwijder alle extra bestanden
        if ($post->extra_files_paths) {
            foreach ($post->extra_files_paths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $post->delete();
        return back()->with('success', 'Blog verwijderd.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
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
        ];

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('blogs', 'slug')->ignore($ignoreId)],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'content' => ['required'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots_index' => ['sometimes', 'boolean'],
            'robots_follow' => ['sometimes', 'boolean'],

            'media_type' => ['required', Rule::in(['image', 'youtube', 'upload'])],
            'youtube_url' => ['nullable', 'required_if:media_type,youtube', 'url'],
            'cover_image' => ['nullable', 'image', 'max:' . self::MAX_IMAGE_KB],
            'video_upload' => ['nullable', 'file', 'mimes:mp4,webm,ogg', 'max:' . self::MAX_VIDEO_KB],
            'og_image' => ['nullable', 'image', 'max:' . self::MAX_IMAGE_KB],

            'category_id' => ['nullable', 'exists:categories,id'],

            'extra_files' => ['nullable', 'array', 'max:3'],
            'extra_files.*' => ['file', 'mimes:pdf,doc,docx,zip', 'max:' . self::MAX_FILE_KB],

            'cover_image_path_keep' => ['nullable', 'string'],
            'video_path_keep' => ['nullable', 'string'],
            'remove_extra_files' => ['nullable', 'array'],
        ];

        // Custom validatie voor download_file om 'DELETE' toe te staan
        if ($request->hasFile('download_file')) {
            $rules['download_file'] = ['file', 'max:' . self::MAX_FILE_KB];
        } else {
            // Als het geen file upload is, mag het 'DELETE' string zijn of null
            $rules['download_file'] = [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_string($value) && $value === 'DELETE') {
                        return;
                    }
                    if (!is_null($value)) {
                        $fail('Het hoofdbestand moet een bestand zijn.');
                    }
                }
            ];
        }

        if ($request->input('media_type') === 'image') {
            $rules['cover_image'] = array_merge($rules['cover_image'], [
                Rule::requiredIf(fn() => empty($request->input('cover_image_path_keep')))
            ]);
        }
        if ($request->input('media_type') === 'upload') {
            $rules['video_upload'] = array_merge($rules['video_upload'], [
                Rule::requiredIf(fn() => empty($request->input('video_path_keep')))
            ]);
        }

        return $request->validate($rules, $messages);
    }

    // ... pageEdit en pageUpdate methoden ...
    public function pageEdit()
    {
        $page = BlogPage::first();
        return Inertia::render('admin/pages/Blog', ['blogPage' => $page]);
    }
    public function pageUpdate(Request $request)
    {
        $page = BlogPage::firstOrCreate(['id' => 1], ['title' => 'Blog']);

        $messages = [
            'required' => 'Dit veld is verplicht.',
            'string' => 'Dit veld moet tekst zijn.',
            'max' => [
                'string' => 'Mag niet meer dan :max tekens bevatten.',
                'file' => 'Bestand mag niet groter zijn dan :max kilobytes.',
            ],
            'boolean' => 'Dit veld moet waar of onwaar zijn.',
            'image' => 'Dit moet een geldig afbeeldingsbestand zijn.',
            'url' => 'Voer een geldige URL in.',
        ];

        $rules = [
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
        ];

        // Validatie voor afbeeldingen
        if ($request->hasFile('image')) {
            $rules['image'] = 'image|max:' . self::MAX_IMAGE_KB;
        }
        if ($request->hasFile('seo_image')) {
            $rules['seo_image'] = 'image|max:' . self::MAX_IMAGE_KB;
        }

        $data = $request->validate($rules, $messages);

        // Afhandeling Hero Image
        // Let op: In Blade/andere controllers heet het soms image_path in DB, maar form stuurt 'image'.
        if ($request->hasFile('image')) {
            if ($page->image_path) {
                Storage::disk('public')->delete($page->image_path);
            }
            $data['image_path'] = $request->file('image')->store('blog/hero', 'public');
        } elseif ($request->input('image') === 'DELETE') {
            if ($page->image_path) {
                Storage::disk('public')->delete($page->image_path);
            }
            $data['image_path'] = null;
        }

        // Afhandeling SEO Image
        if ($request->hasFile('seo_image')) {
            if ($page->seo_image_path) {
                Storage::disk('public')->delete($page->seo_image_path);
            }
            $data['seo_image_path'] = $request->file('seo_image')->store('blog/seo', 'public');
        } elseif ($request->input('seo_image') === 'DELETE') {
            if ($page->seo_image_path) {
                Storage::disk('public')->delete($page->seo_image_path);
            }
            $data['seo_image_path'] = null;
        }

        unset($data['image'], $data['seo_image']);

        $page->update($data);

        return back()->with('success', 'Blogpagina opgeslagen.');
    }
}
