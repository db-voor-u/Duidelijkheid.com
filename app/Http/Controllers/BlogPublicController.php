<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogPage;
use App\Models\Welcome;
use App\Models\Category; // Import Category
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogPublicController extends Controller
{
    // Centrale lijst van media-kolommen om dubbele code te voorkomen
    private const MEDIA_COLUMNS = [
        'media_type',
        'video_path',
        'download_file_path'
    ];

    /** Publieke blog overzichtspagina */
    public function index(Request $request)
    {
        // Haal de blog pagina instellingen op (hero, seo)
        $page = BlogPage::query()->first();

        $categorySlug = $request->query('category');

        $query = Blog::published()
            ->with('category') // Eager load category
            ->latest('published_at')
            ->latest('id')
            ->select(array_merge(
                ['id', 'title', 'slug', 'excerpt', 'cover_image_path', 'og_image_path as seo_image_path', 'published_at', 'category_id'],
                self::MEDIA_COLUMNS
            ));

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        $posts = $query->latest('published_at')->paginate(5)->withQueryString();

        // Haal alle categorieën op die NIET specifiek voor Over Ons of Innovatie zijn (inclusief NULL)
        $categories = Category::where(function ($query) {
            $query->whereNull('type')
                ->orWhereNotIn('type', ['innovatie', 'over_ons']);
        })->get();

        return Inertia::render('blog/Index', [
            'posts' => $posts,
            'categories' => $categories,
            'blogPage' => $page ? $page->only([
                'title',
                'content',
                'image_path',
                'meta_title',
                'meta_description',
                'seo_image_path',
                'canonical_url',
                'robots_index',
                'robots_follow'
            ]) : null,
        ]);
    }

    /** Publieke homepage/landing */
    public function home(): Response
    {
        $w = Welcome::query()->first();
        // Haal alle categorieën op die NIET specifiek voor Over Ons of Innovatie zijn (inclusief NULL)
        $categories = Category::where(function ($query) {
            $query->whereNull('type')
                ->orWhereNotIn('type', ['innovatie', 'over_ons']);
        })->get();

        // Welcome data mapping
        $welcome = [
            'title' => $w?->title ?? 'Welkom!',
            'image_path' => $w?->image_path,
            'content' => $w?->content,
            'meta_title' => $w?->meta_title,
            'meta_description' => $w?->meta_description,
            'seo_image_path' => $w?->seo_image_path,
            'canonical_url' => $w?->canonical_url,
            'robots_index' => (bool) ($w?->robots_index ?? true),
            'robots_follow' => (bool) ($w?->robots_follow ?? true),
        ];

        // Haal laatste 5 blogs op voor de homepage sectie met paginatie
        $latestPosts = Blog::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc')
            ->with('category') // Eager load category
            ->paginate(5, array_merge(
                ['title', 'slug', 'excerpt', 'cover_image_path', 'og_image_path as seo_image_path', 'published_at', 'created_at', 'category_id'], // Add category_id
                self::MEDIA_COLUMNS
            ))
            ->through(fn($p) => [
                'title' => $p->title,
                'slug' => $p->slug,
                'excerpt' => $p->excerpt,
                'cover_image_path' => $p->cover_image_path,
                'seo_image_path' => $p->seo_image_path,
                'published_at' => optional($p->published_at)->toDateString(),
                'category' => $p->category ? [
                    'name' => $p->category->name,
                    'color' => $p->category->color,
                    'slug' => $p->category->slug,
                ] : null,
                // Media velden doorgeven aan de kaarten op Home
                'media_type' => $p->media_type,
                'video_path' => $p->video_path,
                'download_file_path' => $p->download_file_path,
            ]);

        return Inertia::render('Welcome', [
            'welcome' => $welcome,
            'latestPosts' => $latestPosts,
            'categories' => $categories, // Pass categories
        ]);
    }

    /** Publieke blog detailpagina */
    public function show(Blog $post): Response
    {
        abort_unless($post->is_published, 404);

        // Haal vorige/volgende posts op voor navigatie
        $prevPost = Blog::published()
            ->where('published_at', '<', $post->published_at)
            ->latest('published_at')
            ->first(['title', 'slug']);

        $nextPost = Blog::published()
            ->where('published_at', '>', $post->published_at)
            ->oldest('published_at')
            ->first(['title', 'slug']);

        return Inertia::render('blog/Show', [
            'post' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'cover_image_path' => $post->cover_image_path,
                'published_at' => optional($post->published_at)->toDateString(),
                // Media & Download
                'media_type' => $post->media_type,
                'video_path' => $post->video_path,
                'download_file_path' => $post->download_file_path,
                'extra_files_paths' => $post->extra_files_paths,
                // SEO
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'canonical_url' => $post->canonical_url,
                'robots_index' => (bool) ($post->robots_index ?? true),
                'robots_follow' => (bool) ($post->robots_follow ?? true),
                'seo_image_path' => $post->seo_image_path,
            ],
            'prevPost' => $prevPost,
            'nextPost' => $nextPost,
        ]);
    }

    /** Download bestand (forceer download header) */
    public function download(Request $request, Blog $post)
    {
        abort_unless($post->is_published, 404);

        $path = $request->input('path');

        if (!$path) {
            // Main download
            $path = $post->download_file_path;
        } else {
            // Check of het een geldig extra bestand is
            $validPaths = $post->extra_files_paths ?? [];
            if (!in_array($path, $validPaths)) {
                abort(403, 'Bestand hoort niet bij deze blog.');
            }
        }

        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            abort(404, 'Bestand niet gevonden.');
        }

        return response()->download(
            \Illuminate\Support\Facades\Storage::disk('public')->path($path)
        );
    }
}
