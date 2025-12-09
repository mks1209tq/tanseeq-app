<?php

namespace App\Contracts\Services;

interface AuthorizationServiceInterface
{
    /**
     * Check if a user has authorization for the given object with required fields.
     *
     * @param  int  $userId
     * @param  string  $objectCode
     * @param  array<string, string>  $requiredFields
     */
    public function check(int $userId, string $objectCode, array $requiredFields): bool;
}

