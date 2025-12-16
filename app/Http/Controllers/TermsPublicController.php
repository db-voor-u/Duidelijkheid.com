<?php

namespace App\Http\Controllers;

use App\Models\Terms;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TermsPublicController extends Controller
{
    public function show()
    {
        $terms = Terms::first();

        return Inertia::render('Terms', [
            'terms' => $terms ? $terms->only(['title', 'content']) : null,
        ]);
    }
}
