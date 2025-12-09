<?php

namespace Modules\Authentication\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Modules\Authentication\Entities\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Modules\Authentication\Http\Requests\Auth\RegisterRequest;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Only super-admin can access registration
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Only administrators can create new users.');
        }

        return view('authentication::auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        // Only super-admin can create users
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Only administrators can create new users.');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        // Don't auto-login when admin creates a user - redirect back to dashboard
        return redirect()->route('dashboard')
            ->with('status', "User {$user->name} ({$user->email}) created successfully.");
    }
}

