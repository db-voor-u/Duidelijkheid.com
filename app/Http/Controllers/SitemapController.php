<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\OverOnsBlog;
use App\Models\InnovatieBlog;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Static pages
        $staticPages = [
            ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => '/blog', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/over-ons', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['url' => '/innovatie', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['url' => '/algemene-voorwaarden', 'priority' => '0.3', 'changefreq' => 'monthly'],
            ['url' => '/privacy', 'priority' => '0.3', 'changefreq' => 'monthly'],
        ];

        foreach ($staticPages as $page) {
            $content .= $this->urlEntry(
                url($page['url']),
                now()->toW3cString(),
                $page['changefreq'],
                $page['priority']
            );
        }

        // Blog posts
        $blogs = Blog::where('is_published', true)
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc')
            ->get();

        foreach ($blogs as $blog) {
            $content .= $this->urlEntry(
                url("/blog/{$blog->slug}"),
                $blog->updated_at?->toW3cString() ?? $blog->published_at->toW3cString(),
                'weekly',
                '0.7'
            );
        }

        // Over Ons blogs
        if (class_exists(OverOnsBlog::class)) {
            $overOnsBlogs = OverOnsBlog::where('is_published', true)
                ->whereNotNull('published_at')
                ->orderBy('published_at', 'desc')
                ->get();

            foreach ($overOnsBlogs as $blog) {
                $content .= $this->urlEntry(
                    url("/over-ons-blog/{$blog->slug}"),
                    $blog->updated_at?->toW3cString() ?? $blog->published_at->toW3cString(),
                    'weekly',
                    '0.6'
                );
            }
        }

        // Innovatie blogs
        if (class_exists(InnovatieBlog::class)) {
            $innovatieBlogs = InnovatieBlog::where('is_published', true)
                ->whereNotNull('published_at')
                ->orderBy('published_at', 'desc')
                ->get();

            foreach ($innovatieBlogs as $blog) {
                $content .= $this->urlEntry(
                    url("/innovatie/{$blog->slug}"),
                    $blog->updated_at?->toW3cString() ?? $blog->published_at->toW3cString(),
                    'weekly',
                    '0.6'
                );
            }
        }

        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }

    private function urlEntry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return "  <url>\n" .
            "    <loc>{$loc}</loc>\n" .
            "    <lastmod>{$lastmod}</lastmod>\n" .
            "    <changefreq>{$changefreq}</changefreq>\n" .
            "    <priority>{$priority}</priority>\n" .
            "  </url>\n";
    }
}
