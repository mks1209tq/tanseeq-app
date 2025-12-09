<?php

namespace Modules\Authorization\Services;

use Modules\Authentication\Entities\User;
use App\DTOs\UserDTO;
use Illuminate\Support\Facades\Cache;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\AuthorizationDebug\Entities\AuthorizationFailure;

class AuthorizationService
{
    /**
     * Check if a user has authorization for the given object with required fields.
     *
     * @param  User|UserDTO|int  $user
     * @param  string  $objectCode
     * @param  array<string, string>  $requiredFields
     */
    public function check(User|UserDTO|int $user, string $objectCode, array $requiredFields): bool
    {
        // Normalize user to get ID and roles
        $userId = $this->getUserId($user);
        $userRoles = $this->getUserRoles($user);

        // Super-admin completely bypasses all authorization checks
        // No database queries, no logging, immediate return
        $isSuperAdmin = $this->isSuperAdmin($user, $userRoles);
        
        if ($isSuperAdmin) {
            return true;
        }

        // Super-read-only bypasses authorization for read-only operations (ACTVT = '03' Display)
        $isSuperReadOnly = $this->isSuperReadOnly($user, $userRoles);
        
        if ($isSuperReadOnly) {
            // Check if this is a read-only operation
            $activityCode = $requiredFields['ACTVT'] ?? null;
            
            // If no activity specified or activity is '03' (Display), allow access
            if ($activityCode === null || $activityCode === '03') {
                return true;
            }
            
            // For non-read operations, continue with normal authorization check
        }

        // Return false if user has no roles
        if (empty($userRoles)) {
            return false;
        }

        // Check cache first
        $cacheKey = "auth:{$userId}:{$objectCode}";
        $cached = Cache::get($cacheKey);

        $authorizations = null;
        $isAllowed = false;

        if ($cached !== null) {
            $authorizations = $cached;
            $isAllowed = $this->checkCachedAuthorizations($cached, $requiredFields);
        } else {
            // Load RoleAuthorizations for user's roles matching the AuthObject code
            $roleIds = $this->getRoleIdsFromNames($userRoles);

            $authorizations = RoleAuthorization::whereIn('role_id', $roleIds)
                ->whereHas('authObject', function ($query) use ($objectCode) {
                    $query->where('code', $objectCode);
                })
                ->with('fields')
                ->get();

            // Cache for 5 minutes
            Cache::put($cacheKey, $authorizations, now()->addMinutes(5));

            $isAllowed = $this->checkAuthorizations($authorizations, $requiredFields);
        }

        // Log the authorization check (best-effort, exception-safe)
        $this->logAuthorizationCheck($user, $objectCode, $requiredFields, $isAllowed, $authorizations);

        return $isAllowed;
    }

    /**
     * Get user ID from user object or integer.
     */
    protected function getUserId(User|UserDTO|int $user): int
    {
        if (is_int($user)) {
            return $user;
        }

        return $user->id;
    }

    /**
     * Get user roles from user object.
     *
     * @return array<string>
     */
    protected function getUserRoles(User|UserDTO|int $user): array
    {
        if (is_int($user)) {
            // Fetch from authentication service
            $authService = app(\App\Contracts\Services\AuthenticationServiceInterface::class);
            $userDto = $authService->getUserById($user);
            
            return $userDto?->roles ?? [];
        }

        if ($user instanceof UserDTO) {
            return $user->roles;
        }

        // User model - get roles
        return $user->roles()->pluck('name')->toArray();
    }

    /**
     * Get role IDs from role names.
     *
     * @param  array<string>  $roleNames
     * @return array<int>
     */
    protected function getRoleIdsFromNames(array $roleNames): array
    {
        return \Modules\Authorization\Entities\Role::whereIn('name', $roleNames)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Check if any authorization matches the required fields.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, RoleAuthorization>  $authorizations
     * @param  array<string, string>  $requiredFields
     */
    protected function checkAuthorizations($authorizations, array $requiredFields): bool
    {
        foreach ($authorizations as $authorization) {
            if ($this->checkAuthorization($authorization, $requiredFields)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if cached authorizations match the required fields.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, RoleAuthorization>  $authorizations
     * @param  array<string, string>  $requiredFields
     */
    protected function checkCachedAuthorizations($authorizations, array $requiredFields): bool
    {
        return $this->checkAuthorizations($authorizations, $requiredFields);
    }

    /**
     * Check if a single authorization matches all required fields.
     *
     * @param  array<string, string>  $requiredFields
     */
    protected function checkAuthorization(RoleAuthorization $authorization, array $requiredFields): bool
    {
        // Group field rules by field_code
        $fieldRules = $authorization->fields->groupBy('field_code');

        // Check each required field
        foreach ($requiredFields as $fieldCode => $requiredValue) {
            if (! isset($fieldRules[$fieldCode])) {
                // No rule for this field means this authorization doesn't match
                return false;
            }

            // Check if at least one rule for this field matches
            $matches = false;
            foreach ($fieldRules[$fieldCode] as $rule) {
                if ($this->checkFieldRule($rule, $requiredValue)) {
                    $matches = true;
                    break;
                }
            }

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a field rule matches the required value.
     */
    protected function checkFieldRule(RoleAuthorizationField $rule, string $requiredValue): bool
    {
        return match ($rule->operator) {
            '*' => true, // Wildcard: always matches
            '=' => $rule->value_from === $requiredValue,
            'in' => $this->checkInOperator($rule->value_from, $requiredValue),
            'between' => $this->checkBetweenOperator($rule->value_from, $rule->value_to, $requiredValue),
            default => false,
        };
    }

    /**
     * Check if value is in comma-separated list.
     */
    protected function checkInOperator(?string $valueList, string $requiredValue): bool
    {
        if ($valueList === null) {
            return false;
        }

        $values = array_map('trim', explode(',', $valueList));

        return in_array($requiredValue, $values, true);
    }

    /**
     * Check if value is between value_from and value_to.
     */
    protected function checkBetweenOperator(?string $valueFrom, ?string $valueTo, string $requiredValue): bool
    {
        if ($valueFrom === null || $valueTo === null) {
            return false;
        }

        return $requiredValue >= $valueFrom && $requiredValue <= $valueTo;
    }

    /**
     * Check if a user has the super-admin role.
     */
    protected function isSuperAdmin(User|UserDTO|int $user, array $roles = []): bool
    {
        if (empty($roles)) {
            $roles = $this->getUserRoles($user);
        }

        $superAdminRoles = ['SuperAdmin', 'super-admin', 'SUPER_ADMIN'];

        return ! empty(array_intersect($superAdminRoles, $roles));
    }

    /**
     * Check if a user has the super-read-only role.
     */
    protected function isSuperReadOnly(User|UserDTO|int $user, array $roles = []): bool
    {
        if (empty($roles)) {
            $roles = $this->getUserRoles($user);
        }

        $superReadOnlyRoles = ['SuperReadOnly', 'super-read-only', 'SUPER_READ_ONLY', 'READ_ONLY_ADMIN'];

        return ! empty(array_intersect($superReadOnlyRoles, $roles));
    }

    /**
     * Log an authorization check result.
     *
     * @param  User|UserDTO|int  $user
     * @param  string  $objectCode
     * @param  array<string, string>  $requiredFields
     * @param  bool  $isAllowed
     * @param  \Illuminate\Database\Eloquent\Collection<int, RoleAuthorization>|null  $authorizations
     */
    protected function logAuthorizationCheck(User|UserDTO|int $user, string $objectCode, array $requiredFields, bool $isAllowed, $authorizations): void
    {
        try {
            // Only log if user is authenticated (has user_id)
            $userId = $this->getUserId($user);
            if ($userId <= 0) {
                return;
            }

            // Build summary from authorizations
            $summary = $authorizations ? $this->buildSummaryFromAuthorizations($authorizations, $requiredFields) : null;

            // Extract request context
            $routeName = optional(request()->route())->getName();
            $requestPath = request()->path();
            $requestMethod = request()->method();
            $clientIp = request()->ip();
            $userAgent = request()->userAgent();

            // Create authorization failure record
            AuthorizationFailure::create([
                'user_id' => $userId,
                'auth_object_code' => $objectCode,
                'required_fields' => $requiredFields,
                'summary' => $summary,
                'is_allowed' => $isAllowed,
                'route_name' => $routeName,
                'request_path' => $requestPath,
                'request_method' => $requestMethod,
                'client_ip' => $clientIp,
                'user_agent' => $userAgent,
            ]);
        } catch (\Exception $e) {
            // Logging failure should not break authorization check
            // Silently fail - this is best-effort logging
        }
    }

    /**
     * Build summary JSON from authorizations showing what the user has.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, RoleAuthorization>  $authorizations
     * @param  array<string, string>  $requiredFields
     * @return array<string, array<string, array<int, array<string, mixed>>>>
     */
    protected function buildSummaryFromAuthorizations($authorizations, array $requiredFields): array
    {
        $summary = [];

        // For each required field, collect all rules from all authorizations
        foreach ($requiredFields as $fieldCode => $requiredValue) {
            $fieldRules = [];

            foreach ($authorizations as $authorization) {
                $fields = $authorization->fields->where('field_code', $fieldCode);

                foreach ($fields as $field) {
                    $rule = [
                        'operator' => $field->operator,
                    ];

                    // Add values based on operator
                    switch ($field->operator) {
                        case '*':
                            // Wildcard - no specific values
                            break;
                        case '=':
                            $rule['values'] = [$field->value_from];
                            break;
                        case 'in':
                            if ($field->value_from !== null) {
                                $rule['values'] = array_map('trim', explode(',', $field->value_from));
                            }
                            break;
                        case 'between':
                            $rule['values'] = [
                                'from' => $field->value_from,
                                'to' => $field->value_to,
                            ];
                            break;
                    }

                    $fieldRules[] = $rule;
                }
            }

            if (! empty($fieldRules)) {
                $summary[$fieldCode] = [
                    'rules' => $fieldRules,
                ];
            }
        }

        return $summary;
    }
}

