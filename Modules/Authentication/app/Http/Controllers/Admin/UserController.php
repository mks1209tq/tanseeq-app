<?php

namespace Modules\Authentication\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Authentication\Entities\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Authentication\Http\Requests\Admin\StoreUserRequest;
use Illuminate\Support\Facades\Hash;
use App\Events\UserCreated;
use Modules\Navigation\Attributes\NavigationItem;

class UserController extends Controller
{
    /**
     * Show the form for creating a new user.
     */
    #[NavigationItem(label: 'Create User', icon: 'user-plus', order: 4, group: 'admin')]
    public function create(): View
    {
        return view('authentication::admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Dispatch event for inter-service communication
        event(new UserCreated(
            userId: $user->id,
            name: $user->name,
            email: $user->email,
        ));

        return redirect()->route('dashboard')
            ->with('status', "User {$user->name} ({$user->email}) created successfully.");
    }
}

