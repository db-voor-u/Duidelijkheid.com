<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedbacks = Feedback::latest()
            ->paginate(15);

        return Inertia::render('admin/feedback/Index', [
            'feedbacks' => $feedbacks,
        ]);
    }

    public function show(Feedback $feedback)
    {
        // Mark as read when viewing
        if (!$feedback->is_read) {
            $feedback->update(['is_read' => true]);
        }

        return Inertia::render('admin/feedback/Show', [
            'feedback' => [
                'id' => $feedback->id,
                'name' => $feedback->name,
                'email' => $feedback->email,
                'message' => $feedback->message,
                'sentiment' => $feedback->sentiment,
                'page_context' => $feedback->page_context,
                'is_read' => $feedback->is_read,
                'created_at' => $feedback->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Feedback $feedback)
    {
        $feedback->delete();
        return back()->with('success', 'Feedback verwijderd.');
    }

    public function toggleRead(Feedback $feedback)
    {
        $feedback->update(['is_read' => !$feedback->is_read]);
        return back();
    }
}
