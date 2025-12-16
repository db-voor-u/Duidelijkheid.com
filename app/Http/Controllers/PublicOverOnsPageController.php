<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\OverOnsPage;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicOverOnsPageController extends Controller
{
    public function show(Request $request)
    {
        $overOnsPage = OverOnsPage::firstOrCreate(['id' => 1]);

        // Haal alle categorieën op
        $allCategories = Category::all();

        // Filter: Innovatie mag hier NIET tussen staan ("innovatie alleen bij innovatie")
        $validCategories = $allCategories->where('type', '!=', 'innovatie');

        // Splits in 'Over Ons' specifiek en de Rest
        $overOnsCategories = $validCategories->where('type', 'over_ons')->values();
        $otherCategories = $validCategories->where('type', '!=', 'over_ons')->values();

        $categorySlug = $request->query('category');

        // Haal blogs op die NIET 'innovatie' zijn
        $query = \App\Models\OverOnsBlog::where('is_published', true)
            ->whereNotNull('published_at')
            ->whereHas('category', function ($q) {
                $q->where('type', '!=', 'innovatie');
            });

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        $posts = $query->with('category')
            ->latest('published_at')
            ->paginate(5)
            ->withQueryString();

        return Inertia::render('OverOns', [
            'overOnsCategories' => $overOnsCategories,
            'otherCategories' => $otherCategories,
            'posts' => $posts,
            'overOns' => [
                'title' => $overOnsPage->hero_title,
                'content' => $overOnsPage->intro,
                'image_path' => $overOnsPage->hero_image_path,
                'meta_title' => $overOnsPage->seo_title,
                'meta_description' => $overOnsPage->seo_description,
                'canonical_url' => $overOnsPage->canonical_url,
                'robots_index' => $overOnsPage->robots_index,
                'robots_follow' => $overOnsPage->robots_follow,
                'published' => $overOnsPage->published,
                'seo_image_path' => $overOnsPage->seo_image_path,
            ],
        ]);
    }
}
