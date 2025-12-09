<?php

namespace App\Contracts\Services;

use App\DTOs\UserDTO;

interface AuthenticationServiceInterface
{
    /**
     * Get a user by ID.
     */
    public function getUserById(int $userId): ?UserDTO;

    /**
     * Get multiple users by IDs.
     *
     * @param  array<int>  $userIds
     * @return array<int, UserDTO>
     */
    public function getUsersByIds(array $userIds): array;

    /**
     * Check if a user has a specific role.
     */
    public function userHasRole(int $userId, string|array $roles): bool;

    /**
     * Check if a user is a super-admin.
     */
    public function isSuperAdmin(int $userId): bool;

    /**
     * Check if a user is a super-read-only admin.
     */
    public function isSuperReadOnly(int $userId): bool;

    /**
     * Get user roles.
     *
     * @return array<string>
     */
    public function getUserRoles(int $userId): array;
}

