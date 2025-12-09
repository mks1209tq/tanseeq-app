<?php

namespace Modules\Authentication\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Authentication\Http\Requests\Auth\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('authentication::auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        $user = Auth::user();

        // Check if email verification is required
        if (\Modules\Authentication\Entities\AuthSetting::isEnabled('require_email_verification', false)) {
            if (! $user->hasVerifiedEmail()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect(route('verification.notice', absolute: false))
                    ->with('error', 'Please verify your email address before logging in.');
            }
        }

        // Check if 2FA is forced (placeholder for future 2FA implementation)
        if (\Modules\Authentication\Entities\AuthSetting::isEnabled('force_two_factor', false)) {
            // TODO: Implement 2FA check
            // For now, this is a placeholder
        }

        // Get intended URL, but filter out register route for non-super-admins
        $intended = $request->session()->pull('url.intended', route('dashboard', absolute: false));
        
        // If intended URL is register and user is not super-admin, redirect to dashboard
        if ($intended === route('register', absolute: false) && !$user->isSuperAdmin()) {
            $intended = route('dashboard', absolute: false);
        }

        return redirect($intended);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

