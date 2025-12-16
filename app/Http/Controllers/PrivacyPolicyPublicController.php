<?php

namespace App\Http\Controllers;

use App\Models\PrivacyPolicy;
use Inertia\Inertia;

class PrivacyPolicyPublicController extends Controller
{
    public function show()
    {
        $policy = PrivacyPolicy::first();

        return Inertia::render('Privacy', [
            'policy' => $policy ? $policy->only(['title', 'content']) : null,
        ]);
    }
}
