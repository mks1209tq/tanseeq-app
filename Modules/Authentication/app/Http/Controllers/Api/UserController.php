<?php

namespace Modules\Authentication\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authentication\Entities\User;
use App\DTOs\UserDTO;

class UserController extends Controller
{
    /**
     * Get a user by ID.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);

        $dto = new UserDTO(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            emailVerifiedAt: $user->email_verified_at?->toIso8601String(),
            roles: $user->roles->pluck('name')->toArray(),
        );

        return response()->json([
            'data' => $dto->toArray(),
        ]);
    }

    /**
     * Get multiple users by IDs.
     */
    public function batch(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $users = User::with('roles')
            ->whereIn('id', $request->ids)
            ->get();

        $data = [];
        foreach ($users as $user) {
            $dto = new UserDTO(
                id: $user->id,
                name: $user->name,
                email: $user->email,
                emailVerifiedAt: $user->email_verified_at?->toIso8601String(),
                roles: $user->roles->pluck('name')->toArray(),
            );
            $data[] = $dto->toArray();
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Check if user has role.
     */
    public function hasRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string',
        ]);

        $user = User::with('roles')->findOrFail($id);
        $hasRole = $user->hasRole($request->roles);

        return response()->json([
            'has_role' => $hasRole,
        ]);
    }

    /**
     * Get user roles.
     */
    public function roles(int $id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);

        return response()->json([
            'roles' => $user->roles->pluck('name')->toArray(),
        ]);
    }
}

