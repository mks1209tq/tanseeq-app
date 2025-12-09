<?php

namespace App\Services\Clients;

use App\Contracts\Services\AuthenticationServiceInterface;
use App\DTOs\UserDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuthenticationServiceClient implements AuthenticationServiceInterface
{
    protected string $baseUrl;

    protected int $cacheTtl = 300; // 5 minutes

    public function __construct()
    {
        // In a real microservice, this would come from service discovery or config
        $this->baseUrl = config('services.authentication.url', 'http://authentication-service.test');
    }

    /**
     * Get a user by ID.
     * 
     * Note: This client is only used in microservice mode.
     * In monolith mode, LocalAuthenticationService is used instead.
     */
    public function getUserById(int $userId): ?UserDTO
    {
        $cacheKey = "auth_service:user:{$userId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId) {
            try {
                $response = Http::timeout(5)
                    ->get("{$this->baseUrl}/api/v1/users/{$userId}");

                if ($response->successful()) {
                    return UserDTO::fromArray($response->json('data'));
                }

                Log::warning("Failed to fetch user {$userId} from authentication service", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error("Error fetching user {$userId} from authentication service", [
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Get multiple users by IDs.
     * 
     * Note: This client is only used in microservice mode.
     * In monolith mode, LocalAuthenticationService is used instead.
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
                $response = Http::timeout(10)
                    ->post("{$this->baseUrl}/api/v1/users/batch", ['ids' => $userIds]);

                if ($response->successful()) {
                    $users = [];
                    foreach ($response->json('data', []) as $userData) {
                        $dto = UserDTO::fromArray($userData);
                        $users[$dto->id] = $dto;
                    }

                    return $users;
                }

                Log::warning('Failed to fetch users from authentication service', [
                    'status' => $response->status(),
                    'ids' => $userIds,
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('Error fetching users from authentication service', [
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

