<?php

namespace App\Http\Controllers;

use App\Models\OverOnsBlog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OverOnsPublicController extends Controller
{
    /**
     * Publieke detailpagina voor Over Ons Blogposts.
     * * @param OverOnsBlog $post - Automatische Route Model Binding via slug
     */
    public function show(OverOnsBlog $post): Response
    {
        // Check of de post gepubliceerd is
        abort_unless($post->is_published, 404);

        $post->load('category');
        $context = ($post->category && $post->category->type === 'innovatie') ? 'innovatie' : 'over_ons';

        // We selecteren de data handmatig om volledige controle te hebben over wat naar de frontend gaat
        $postData = [
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

            // SEO
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'canonical_url' => $post->canonical_url,
            'robots_index' => (bool) ($post->robots_index ?? true),
            'robots_follow' => (bool) ($post->robots_follow ?? true),
            'seo_image_path' => $post->og_image_path,
        ];

        // Navigatie Logic (Binnen dezelfde context/type)
        $prev = OverOnsBlog::published()
            ->where('id', '!=', $post->id)
            ->whereHas('category', fn($q) => $q->where('type', $context))
            ->where(function ($query) use ($post) {
                $query->where('published_at', '<', $post->published_at)
                    ->orWhere(function ($q) use ($post) {
                        $q->where('published_at', '=', $post->published_at)
                            ->where('id', '<', $post->id);
                    });
            })
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc')
            ->first(['title', 'slug']);

        $next = OverOnsBlog::published()
            ->where('id', '!=', $post->id)
            ->whereHas('category', fn($q) => $q->where('type', $context))
            ->where(function ($query) use ($post) {
                $query->where('published_at', '>', $post->published_at)
                    ->orWhere(function ($q) use ($post) {
                        $q->where('published_at', '=', $post->published_at)
                            ->where('id', '>', $post->id);
                    });
            })
            ->orderBy('published_at', 'asc')
            ->orderBy('id', 'asc')
            ->first(['title', 'slug']);

        // We gebruiken nu de specifieke template 'overonsblog/Show.vue'
        return Inertia::render('overonsblog/Show', [
            'post' => $postData,
            'prevPost' => $prev ? ['title' => $prev->title, 'slug' => $prev->slug] : null,
            'nextPost' => $next ? ['title' => $next->title, 'slug' => $next->slug] : null,
            'section' => $context,
        ]);
    }
}
