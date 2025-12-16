<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/categories/Index', [
            'categories' => Category::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:50', // Tailwind class usually
            'type' => 'required|string|in:blog,over_ons,innovatie,neurodiversiteit',
        ]);

        $data['slug'] = Str::slug($data['name']);

        Category::create($data);

        return back()->with('success', 'Categorie aangemaakt.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:50',
            'type' => 'required|string|in:blog,over_ons,innovatie,neurodiversiteit',
        ]);

        if ($data['name'] !== $category->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return back()->with('success', 'Categorie bijgewerkt.');
    }

    public function destroy(Category $category)
    {
        // Optional: check if blogs exist? Logic constrained to nullOnDelete in DB.
        $category->delete();
        return back()->with('success', 'Categorie verwijderd.');
    }
}
