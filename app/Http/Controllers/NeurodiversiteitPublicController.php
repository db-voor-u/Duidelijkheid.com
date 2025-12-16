<?php

namespace App\Http\Controllers;

use App\Models\OverOnsBlog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NeurodiversiteitPublicController extends Controller
{
    public function show()
    {
        // Haal posts op voor Neurodiversiteit
        // Dit kan later uitgebreid worden met meerdere categorieën (bv. ADHD, Autisme)
        // Voor nu filteren we op 'Neurodiversiteit' OF 'ADHD' OF 'Autisme' of de hoofdcategorie 'Neurodiversiteit'

        $latestPosts = OverOnsBlog::where('is_published', true)
            ->whereNotNull('published_at')
            ->whereHas('category', function ($query) {
                $query->whereIn('name', ['Neurodiversiteit', 'ADHD', 'Autisme', 'HSP']);
            })
            ->latest('published_at')
            ->limit(9)
            ->get([
                'title',
                'slug',
                'excerpt',
                'cover_image_path',
                'published_at',
                'media_type',
                'video_path',
                'download_file_path'
            ]);

        return Inertia::render('Neurodiversiteit', [
            'latestPosts' => $latestPosts,
        ]);
    }
}
