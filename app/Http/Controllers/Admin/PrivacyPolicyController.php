<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivacyPolicy;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrivacyPolicyController extends Controller
{
    public function edit()
    {
        $policy = PrivacyPolicy::first();

        return Inertia::render('admin/pages/Privacy', [
            'policy' => $policy ? $policy->only(['title', 'content']) : null,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $policy = PrivacyPolicy::first();
        if ($policy) {
            $policy->update($data);
        } else {
            PrivacyPolicy::create($data);
        }

        return back()->with('success', 'Privacybeleid opgeslagen.');
    }
}
