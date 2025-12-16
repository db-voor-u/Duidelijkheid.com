<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Welcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\Category; // Import Category

class WelcomeController extends Controller
{
    /** Toon admin formulier */
    public function edit(): Response
    {
        $w = Welcome::first();
        $categories = Category::all(); // Fetch all categories

        return Inertia::render('admin/pages/Welcome', [
            'categories' => $categories, // Pass categories
            'welcome' => $w ? $w->only([
                'title',
                'image_path',
                'content',
                'meta_title',
                'meta_description',
                'canonical_url',
                'robots_index',
                'robots_follow',
                'seo_image_path',
            ]) : null,
        ]);
    }

    /** Sla admin formulier op */
    public function update(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots_index' => ['sometimes', 'boolean'],
            'robots_follow' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'seo_image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('welcome', 'public');
        }
        if ($request->hasFile('seo_image')) {
            $data['seo_image_path'] = $request->file('seo_image')->store('welcome', 'public');
        }

        $data['robots_index'] = (bool) ($data['robots_index'] ?? true);
        $data['robots_follow'] = (bool) ($data['robots_follow'] ?? true);

        $w = Welcome::first();
        if ($w) {
            if (isset($data['image_path']) && $w->image_path) {
                Storage::disk('public')->delete($w->image_path);
            }
            if (isset($data['seo_image_path']) && $w->seo_image_path) {
                Storage::disk('public')->delete($w->seo_image_path);
            }
            $w->update($data);
        } else {
            $w = Welcome::create($data);
        }

        return back()->with('success', 'Welkom-inhoud opgeslagen.');
    }
}
