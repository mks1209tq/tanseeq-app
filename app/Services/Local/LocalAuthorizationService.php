<?php

namespace App\Services\Local;

use App\Contracts\Services\AuthorizationServiceInterface;
use Modules\Authorization\Http\Controllers\Api\AuthorizationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LocalAuthorizationService implements AuthorizationServiceInterface
{
    protected int $cacheTtl = 300; // 5 minutes

    /**
     * Check if a user has authorization for the given object with required fields.
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
                $controller = app(AuthorizationController::class);
                $request = Request::create('/api/v1/authorizations/check', 'POST', [
                    'user_id' => $userId,
                    'object_code' => $objectCode,
                    'required_fields' => $requiredFields,
                ]);
                $response = $controller->check($request);

                if ($response->getStatusCode() !== 200) {
                    return false;
                }

                $data = json_decode($response->getContent(), true);

                return $data['authorized'] ?? false;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error checking authorization via local service', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'object_code' => $objectCode,
                ]);

                return false;
            }
        });
    }
}

