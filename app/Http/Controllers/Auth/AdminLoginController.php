<?php
// In app/Http/Controllers/Auth/AdminLoginController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminLoginController extends Controller
{
    protected int $maxAttempts = 5;
    protected int $decayMinutes = 15;

    public function showLoginForm(): Response
    {
        return Inertia::render('admin/AdminLogin');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Het e-mailadres is verplicht.',
            'email.email' => 'Voer een geldig e-mailadres in.',
            'password. required' => 'Het wachtwoord is verplicht.',
        ]);

        $this->ensureIsNotRateLimited($request);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::guard('admin')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            RateLimiter::clear($this->throttleKey($request));

            /** @var \App\Models\Admin $user */
            $user = Auth::guard('admin')->user();
            $user->forceFill(['last_login_at' => now()])->save();

            return redirect()->intended('/hoofdbeheerder/dashboard');
        }

        RateLimiter::hit($this->throttleKey($request), $this->decayMinutes * 60);

        throw ValidationException::withMessages([
            'email' => ['De ingevoerde gegevens zijn onjuist. '],
        ]);
    }

    /**
     * VEILIGE LOGOUT IMPLEMENTATIE
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::guard('admin')->user();

            // Log logout activiteit voor audit trail
            if ($user) {
                \Log::info('Admin logout', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()
                ]);
            }

            // 1. Logout van admin guard
            Auth::guard('admin')->logout();

            // 2.  Invalidate alle sessies
            $request->session()->invalidate();

            // 3. Regenerate CSRF token
            $request->session()->regenerateToken();

            // 4.  Clear remember me token
            if ($user && $user->remember_token) {
                $user->update(['remember_token' => null]);
            }

            // 5. Return naar login met success message
            return redirect()->route('admin.login')->with([
                'status' => 'Je bent veilig uitgelogd.',
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            \Log::error('Logout error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Force logout ondanks error
            Auth::guard('admin')->logout();
            $request->session()->flush();

            return redirect()->route('admin.login')->with([
                'status' => 'Er is een probleem opgetreden, maar je bent uitgelogd.',
                'type' => 'warning'
            ]);
        }
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));
        $minutes = ceil($seconds / 60);

        throw ValidationException::withMessages([
            'email' => [
                "Te veel login pogingen. Probeer het opnieuw over {$minutes} " .
                ($minutes === 1 ? 'minuut' : 'minuten') . "."
            ],
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->input('email')) . '|' . $request->ip()
        );
    }
}
