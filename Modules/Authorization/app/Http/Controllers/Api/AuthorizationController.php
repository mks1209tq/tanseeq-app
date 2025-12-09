<?php

namespace Modules\Authorization\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Services\AuthorizationService;

class AuthorizationController extends Controller
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Check if a user has authorization for the given object with required fields.
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'object_code' => 'required|string',
            'required_fields' => 'required|array',
        ]);

        $authorized = $this->authorizationService->check(
            $request->user_id,
            $request->object_code,
            $request->required_fields
        );

        return response()->json([
            'authorized' => $authorized,
        ]);
    }
}

