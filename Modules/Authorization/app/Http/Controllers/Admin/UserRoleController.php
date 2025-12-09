<?php

namespace Modules\Authorization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Authentication\Entities\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Observers\AuthorizationCacheObserver;

class UserRoleController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $roles = Role::all();
        $user->load('roles');

        return view('authorization::admin.users.edit-roles', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(User $user): RedirectResponse
    {
        $roleIds = request()->input('roles', []);
        $previousRoleIds = $user->roles()->pluck('roles.id')->toArray();

        $user->roles()->sync($roleIds);

        // Clear cache for this user since their roles changed
        $observer = app(AuthorizationCacheObserver::class);

        // Clear cache for removed roles
        $removedRoleIds = array_diff($previousRoleIds, $roleIds);
        foreach ($removedRoleIds as $roleId) {
            $observer->userRoleDetached($user->id, $roleId);
        }

        // Clear cache for added roles
        $addedRoleIds = array_diff($roleIds, $previousRoleIds);
        foreach ($addedRoleIds as $roleId) {
            $observer->userRoleAttached($user->id, $roleId);
        }

        // Also clear all cache for this user to be safe
        $this->clearAllCacheForUser($user->id);

        return redirect()->route('admin.authorization.users.edit-roles', $user)
            ->with('success', 'User roles updated successfully.');
    }

    /**
     * Clear all authorization cache for a user.
     */
    protected function clearAllCacheForUser(int $userId): void
    {
        // Clear authentication service cache (user DTO with roles)
        Cache::forget("auth_service:user:{$userId}");

        // Get all possible auth object codes
        $authObjectCodes = AuthObject::pluck('code')->toArray();

        foreach ($authObjectCodes as $objectCode) {
            Cache::forget("auth:{$userId}:{$objectCode}");
        }
    }
}

