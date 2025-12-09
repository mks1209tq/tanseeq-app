<?php

namespace Modules\Todo\Policies;

use Modules\Authentication\Entities\User;
use Modules\Todo\Entities\Todo;
use App\Contracts\Services\AuthenticationServiceInterface;

class TodoPolicy
{
    public function __construct(
        protected AuthenticationServiceInterface $authService
    ) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super-admin can do everything
        if ($this->authService->isSuperAdmin($user->id)) {
            return true;
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Todo $todo): bool
    {
        // Super-admin can do everything
        if ($this->authService->isSuperAdmin($user->id)) {
            return true;
        }

        return $user->id === $todo->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super-admin can do everything
        if ($this->authService->isSuperAdmin($user->id)) {
            return true;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Todo $todo): bool
    {
        // Super-admin can do everything
        if ($this->authService->isSuperAdmin($user->id)) {
            return true;
        }

        return $user->id === $todo->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Todo $todo): bool
    {
        // Super-admin can do everything
        if ($this->authService->isSuperAdmin($user->id)) {
            return true;
        }

        return $user->id === $todo->user_id;
    }
}
