<?php

namespace App\Services\Local;

use App\Contracts\Services\AuthenticationServiceInterface;
use App\DTOs\UserDTO;
use Modules\Authentication\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LocalAuthenticationService implements AuthenticationServiceInterface
{
    protected int $cacheTtl = 300; // 5 minutes

    /**
     * Get a user by ID.
     */
    public function getUserById(int $userId): ?UserDTO
    {
        $cacheKey = "auth_service:user:{$userId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId) {
            $controller = app(UserController::class);
            $response = $controller->show($userId);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode($response->getContent(), true);

            if (! isset($data['data'])) {
                return null;
            }

            return UserDTO::fromArray($data['data']);
        });
    }

    /**
     * Get multiple users by IDs.
     *
     * @param  array<int>  $userIds
     * @return array<int, UserDTO>
     */
    public function getUsersByIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $cacheKey = 'auth_service:users:'.md5(implode(',', $userIds));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userIds) {
            try {
                $controller = app(UserController::class);
                $request = Request::create('/api/v1/users/batch', 'POST', ['ids' => $userIds]);
                $response = $controller->batch($request);

                if ($response->getStatusCode() !== 200) {
                    return [];
                }

                $data = json_decode($response->getContent(), true);
                $users = [];

                foreach ($data['data'] ?? [] as $userData) {
                    $dto = UserDTO::fromArray($userData);
                    $users[$dto->id] = $dto;
                }

                return $users;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error fetching users via local service', [
                    'error' => $e->getMessage(),
                    'ids' => $userIds,
                ]);

                return [];
            }
        });
    }

    /**
     * Check if a user has a specific role.
     */
    public function userHasRole(int $userId, string|array $roles): bool
    {
        $user = $this->getUserById($userId);

        if (! $user) {
            return false;
        }

        return $user->hasRole($roles);
    }

    /**
     * Check if a user is a super-admin.
     */
    public function isSuperAdmin(int $userId): bool
    {
        return $this->userHasRole($userId, ['SuperAdmin', 'super-admin', 'SUPER_ADMIN']);
    }

    /**
     * Check if a user is a super-read-only admin.
     */
    public function isSuperReadOnly(int $userId): bool
    {
        return $this->userHasRole($userId, ['SuperReadOnly', 'super-read-only', 'SUPER_READ_ONLY', 'READ_ONLY_ADMIN']);
    }

    /**
     * Get user roles.
     *
     * @return array<string>
     */
    public function getUserRoles(int $userId): array
    {
        $user = $this->getUserById($userId);

        if (! $user) {
            return [];
        }

        return $user->roles;
    }
}

