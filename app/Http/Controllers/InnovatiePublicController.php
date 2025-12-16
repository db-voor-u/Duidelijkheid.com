<?php

namespace App\Http\Controllers;

use App\Models\OverOnsBlog;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\InnovatiePage;

use App\Models\Category;

class InnovatiePublicController extends Controller
{
    public function show(Request $request)
    {
        $page = InnovatiePage::firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Gedreven door Innovatie',
                'content' => '<p>Wij bouwen razendsnelle, schaalbare applicaties met de allernieuwste technologie.</p>',
            ]
        );

        $categories = Category::where('type', 'innovatie')->get();

        $categorySlug = $request->query('category');

        // Haal posts op uit 'OverOnsBlog' model (voorheen op Over Ons pagina)
        // Nu getoond op de Innovatie pagina
        $query = OverOnsBlog::where('is_published', true)
            ->whereNotNull('published_at')
            ->whereHas('category', function ($query) {
                $query->where('type', 'innovatie');
            });

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        $latestPosts = $query->with('category')
            ->latest('published_at')
            ->paginate(5)
            ->withQueryString();

        return Inertia::render('Innovatie', [
            'latestPosts' => $latestPosts,
            'categories' => $categories,
            'page' => $page
        ]);
    }
}
