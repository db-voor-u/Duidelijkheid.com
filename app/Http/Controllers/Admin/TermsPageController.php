<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Terms;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TermsPageController extends Controller
{
    public function edit()
    {
        $terms = Terms::first();

        return Inertia::render('admin/pages/Terms', [
            'terms' => $terms ? $terms->only(['title', 'content']) : null,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $terms = Terms::first();
        if ($terms) {
            $terms->update($data);
        } else {
            Terms::create($data);
        }

        return back()->with('success', 'Algemene voorwaarden opgeslagen.');
    }
}
