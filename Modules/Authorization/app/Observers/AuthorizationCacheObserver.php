<?php

namespace Modules\Authorization\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\Authorization\Entities\RoleAuthorizationField;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\AuthObject;

class AuthorizationCacheObserver
{
    /**
     * Clear authorization cache for all users with the given role.
     */
    protected function clearCacheForRole(Role $role): void
    {
        // Get all users with this role
        $userIds = DB::connection('authorization')
            ->table('role_user')
            ->where('role_id', $role->id)
            ->pluck('user_id')
            ->toArray();

        // Get all auth objects this role has authorizations for
        $authObjectCodes = $role->roleAuthorizations()
            ->with('authObject')
            ->get()
            ->pluck('authObject.code')
            ->filter()
            ->unique()
            ->toArray();

        // Clear cache for each user + auth object combination
        foreach ($userIds as $userId) {
            foreach ($authObjectCodes as $objectCode) {
                Cache::forget("auth:{$userId}:{$objectCode}");
            }
        }
    }

    /**
     * Clear authorization cache for a specific user.
     */
    protected function clearCacheForUser(int $userId): void
    {
        // Clear authentication service cache (user DTO with roles)
        Cache::forget("auth_service:user:{$userId}");
        
        // Clear batch user cache patterns (if any)
        // Note: We can't easily clear batch caches without knowing the exact keys,
        // but individual user cache clearing should be sufficient for most cases

        // Get all roles for this user
        $roleIds = DB::connection('authorization')
            ->table('role_user')
            ->where('user_id', $userId)
            ->pluck('role_id')
            ->toArray();

        if (empty($roleIds)) {
            // User has no roles, clear all possible cache keys for this user
            $this->clearAllCacheForUser($userId);
            return;
        }

        // Get all auth objects these roles have authorizations for
        $authObjectCodes = RoleAuthorization::whereIn('role_id', $roleIds)
            ->with('authObject')
            ->get()
            ->pluck('authObject.code')
            ->filter()
            ->unique()
            ->toArray();

        // Clear cache for each auth object
        foreach ($authObjectCodes as $objectCode) {
            Cache::forget("auth:{$userId}:{$objectCode}");
        }
    }

    /**
     * Clear all authorization cache for a user (when user has no roles).
     */
    protected function clearAllCacheForUser(int $userId): void
    {
        // Get all possible auth object codes
        $authObjectCodes = AuthObject::pluck('code')->toArray();

        foreach ($authObjectCodes as $objectCode) {
            Cache::forget("auth:{$userId}:{$objectCode}");
        }
    }

    /**
     * Handle RoleAuthorization created event.
     */
    public function created(RoleAuthorization|RoleAuthorizationField $model): void
    {
        if ($model instanceof RoleAuthorization) {
            $model->load('role', 'authObject');
            if ($model->role) {
                $this->clearCacheForRole($model->role);
            }
        } elseif ($model instanceof RoleAuthorizationField) {
            $model->load('roleAuthorization.role');
            if ($model->roleAuthorization && $model->roleAuthorization->role) {
                $this->clearCacheForRole($model->roleAuthorization->role);
            }
        }
    }

    /**
     * Handle RoleAuthorization updated event.
     */
    public function updated(RoleAuthorization|RoleAuthorizationField $model): void
    {
        if ($model instanceof RoleAuthorization) {
            $model->load('role', 'authObject');
            if ($model->role) {
                $this->clearCacheForRole($model->role);
            }
        } elseif ($model instanceof RoleAuthorizationField) {
            $model->load('roleAuthorization.role');
            if ($model->roleAuthorization && $model->roleAuthorization->role) {
                $this->clearCacheForRole($model->roleAuthorization->role);
            }
        }
    }

    /**
     * Handle RoleAuthorization deleted event.
     */
    public function deleted(RoleAuthorization|RoleAuthorizationField $model): void
    {
        if ($model instanceof RoleAuthorization) {
            // Load before deletion if possible
            if ($model->exists) {
                $model->load('role', 'authObject');
                if ($model->role) {
                    $this->clearCacheForRole($model->role);
                }
            } else {
                // If already deleted, we need to get role from the original attributes
                $roleId = $model->getOriginal('role_id') ?? $model->role_id;
                if ($roleId) {
                    $role = Role::find($roleId);
                    if ($role) {
                        $this->clearCacheForRole($role);
                    }
                }
            }
        } elseif ($model instanceof RoleAuthorizationField) {
            // Load before deletion if possible
            if ($model->exists) {
                $model->load('roleAuthorization.role');
                if ($model->roleAuthorization && $model->roleAuthorization->role) {
                    $this->clearCacheForRole($model->roleAuthorization->role);
                }
            } else {
                // If already deleted, get role authorization from original attributes
                $roleAuthId = $model->getOriginal('role_authorization_id') ?? $model->role_authorization_id;
                if ($roleAuthId) {
                    $roleAuth = RoleAuthorization::find($roleAuthId);
                    if ($roleAuth && $roleAuth->role) {
                        $this->clearCacheForRole($roleAuth->role);
                    }
                }
            }
        }
    }

    /**
     * Handle user role attached event.
     */
    public function userRoleAttached(int $userId, int $roleId): void
    {
        $this->clearCacheForUser($userId);
    }

    /**
     * Handle user role detached event.
     */
    public function userRoleDetached(int $userId, int $roleId): void
    {
        $this->clearCacheForUser($userId);
    }
}

