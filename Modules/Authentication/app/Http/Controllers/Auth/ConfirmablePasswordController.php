<?php

namespace Modules\Authentication\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Authentication\Http\Requests\Auth\PasswordConfirmationRequest;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): View
    {
        return view('authentication::auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(PasswordConfirmationRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->passwordConfirmed();

        return redirect()->intended();
    }
}

