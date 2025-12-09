<?php

namespace App\Services\Clients;

use App\Contracts\Services\AuthorizationServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuthorizationServiceClient implements AuthorizationServiceInterface
{
    protected string $baseUrl;

    protected int $cacheTtl = 300; // 5 minutes

    public function __construct()
    {
        // In a real microservice, this would come from service discovery or config
        $this->baseUrl = config('services.authorization.url', 'http://authorization-service.test');
    }

    /**
     * Check if a user has authorization for the given object with required fields.
     * 
     * Note: This client is only used in microservice mode.
     * In monolith mode, LocalAuthorizationService is used instead.
     *
     * @param  int  $userId
     * @param  string  $objectCode
     * @param  array<string, string>  $requiredFields
     */
    public function check(int $userId, string $objectCode, array $requiredFields): bool
    {
        $cacheKey = "authz_service:check:{$userId}:{$objectCode}:".md5(json_encode($requiredFields));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId, $objectCode, $requiredFields) {
            try {
                $response = Http::timeout(5)
                    ->post("{$this->baseUrl}/api/v1/authorizations/check", [
                        'user_id' => $userId,
                        'object_code' => $objectCode,
                        'required_fields' => $requiredFields,
                    ]);

                if ($response->successful()) {
                    return $response->json('authorized', false);
                }

                Log::warning('Failed to check authorization', [
                    'status' => $response->status(),
                    'user_id' => $userId,
                    'object_code' => $objectCode,
                ]);

                return false;
            } catch (\Exception $e) {
                Log::error('Error checking authorization', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'object_code' => $objectCode,
                ]);

                return false;
            }
        });
    }
}

