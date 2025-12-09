<?php

namespace Modules\Authorization\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Services\AuthorizationService;

trait HasRolesAndAuthorizations
{
    /**
     * Get the roles that belong to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_user', // pivot table
            'user_id', // foreign key on pivot table
            'role_id' // related key on pivot table
        );
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param  string|array<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $roleNames = is_array($roles) ? $roles : [$roles];

        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    /**
     * Check if the user has authorization for the given object with required fields.
     *
     * @param  array<string, string>  $requiredFields
     */
    public function hasAuthObject(string $objectCode, array $requiredFields): bool
    {
        $authorizationService = app(AuthorizationService::class);

        return $authorizationService->check($this, $objectCode, $requiredFields);
    }

    /**
     * Check if the user is a super-admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(['SuperAdmin', 'super-admin', 'SUPER_ADMIN']);
    }

    /**
     * Check if the user is a super-read-only admin.
     */
    public function isSuperReadOnly(): bool
    {
        return $this->hasRole(['SuperReadOnly', 'super-read-only', 'SUPER_READ_ONLY', 'READ_ONLY_ADMIN']);
    }
}
