<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'message' => 'nullable|string|max:1000',
            'sentiment' => 'nullable|string|in:positive,negative',
            'page_context' => 'nullable|string|max:255',
        ]);

        Feedback::create($validated);

        return back()->with('success', 'Bedankt voor je feedback!');
    }
}
