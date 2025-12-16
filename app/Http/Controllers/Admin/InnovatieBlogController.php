<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\OverOnsBlog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class InnovatieBlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $query = OverOnsBlog::query()
            ->whereHas('category', function ($q) {
                $q->where('type', 'innovatie');
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $posts = $query
            ->with('category')
            ->latest('published_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $rootQuery = OverOnsBlog::whereHas('category', fn($q) => $q->where('type', 'innovatie'));

        return Inertia::render('admin/pages/innovatie/Index', [
            'posts' => $posts,
            'filters' => ['search' => $search],
            'stats' => [
                'total' => (clone $rootQuery)->count(),
                'published' => (clone $rootQuery)->where('is_published', true)->count(),
                'drafts' => (clone $rootQuery)->where('is_published', false)->count(),
                'per_page' => $posts->perPage(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/pages/innovatie/Form', [
            'post' => null,
            'categories' => Category::where('type', 'innovatie')->select('id', 'name', 'color')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:over_ons_blogs,slug',
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('type', 'innovatie'),
            ],
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'introduction' => 'nullable|string',

            // SEO
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',

            // Media
            'cover_image' => 'nullable|image|max:2048',
            'seo_image' => 'nullable|image|max:2048',
            'media_type' => 'required|in:image,youtube,upload',
            'video_upload' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/webm|max:51200', // 50MB
            'youtube_url' => 'nullable|url',
            'download_file' => 'nullable|file|max:10240', // 10MB
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Handle Image Uploads
        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('over-ons/covers', 'public');
        }

        if ($request->hasFile('seo_image')) {
            $validated['og_image_path'] = $request->file('seo_image')->store('over-ons/seo', 'public');
        }

        // Handle Video/Download
        if ($validated['media_type'] === 'upload' && $request->hasFile('video_upload')) {
            $validated['video_path'] = $request->file('video_upload')->store('over-ons/videos', 'public');
        } elseif ($validated['media_type'] === 'youtube') {
            $validated['video_path'] = $validated['youtube_url'] ?? null;
        }

        if ($request->hasFile('download_file')) {
            $validated['download_file_path'] = $request->file('download_file')->store('over-ons/downloads', 'public');
        }

        OverOnsBlog::create($validated);

        return redirect()->route('admin.innovatieBlog.index')
            ->with('success', 'Innovatie artikel succesvol aangemaakt.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OverOnsBlog $post)
    {
        // Ensure we are only editing innovatie posts
        $post->load('category');
        if ($post->category && $post->category->type !== 'innovatie') {
            return redirect()->route('admin.overons-blog.edit', $post);
        }

        return Inertia::render('admin/pages/innovatie/Form', [
            'post' => $post,
            'categories' => Category::where('type', 'innovatie')->select('id', 'name', 'color')->get(),
            'success' => session('success'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OverOnsBlog $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('over_ons_blogs', 'slug')->ignore($post->id)],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('type', 'innovatie'),
            ],
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',

            // SEO
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',

            // Media
            'media_type' => 'required|in:image,youtube,upload',
            'youtube_url' => 'nullable|url',

            // File replacements (nullable)
            'cover_image' => 'nullable|image|max:2048',
            'seo_image' => 'nullable|image|max:2048',
            'video_upload' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/webm|max:51200',
            'download_file' => 'nullable|file|max:10240',
            'remove_download' => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Handle Files
        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('over-ons/covers', 'public');
        }
        if ($request->hasFile('seo_image')) {
            $validated['og_image_path'] = $request->file('seo_image')->store('over-ons/seo', 'public');
        }

        if ($validated['media_type'] === 'upload' && $request->hasFile('video_upload')) {
            $validated['video_path'] = $request->file('video_upload')->store('over-ons/videos', 'public');
        } elseif ($validated['media_type'] === 'youtube') {
            $validated['video_path'] = $validated['youtube_url'] ?? null;
        }

        if ($request->hasFile('download_file')) {
            $validated['download_file_path'] = $request->file('download_file')->store('over-ons/downloads', 'public');
        } elseif ($request->boolean('remove_download')) {
            $validated['download_file_path'] = null;
        }

        $post->update($validated);

        return redirect()->route('admin.innovatieBlog.index')
            ->with('success', 'Innovatie artikel bijgewerkt.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OverOnsBlog $post)
    {
        $post->delete();
        return redirect()->route('admin.innovatieBlog.index')
            ->with('success', 'Artikel verwijderd.');
    }
}
