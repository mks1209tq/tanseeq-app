<?php

namespace Modules\Authorization\Services;

use Modules\Authorization\Entities\PrivilegedActivityLog;
use Illuminate\Http\Request;

class PrivilegedActivityLogger
{
    /**
     * Log a privileged activity (SuperAdmin or SuperReadOnly bypass).
     */
    public function log(int $userId, string $roleType, string $objectCode, ?string $activityCode, array $requiredFields, Request $request, ?string $notes = null): void
    {
        try {
            // Only log if user is authenticated
            if ($userId <= 0) {
                return;
            }

            // Extract request data (only for POST/PUT/PATCH methods)
            $requestData = null;
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
                // Get all input data, but exclude sensitive fields
                $requestData = $request->except(['password', 'password_confirmation', '_token', '_method']);
                
                // Limit size to prevent huge payloads
                if (strlen(json_encode($requestData)) > 10000) {
                    $requestData = ['_truncated' => true, 'size' => strlen(json_encode($requestData))];
                }
            }

            PrivilegedActivityLog::create([
                'user_id' => $userId,
                'role_type' => $roleType,
                'auth_object_code' => $objectCode,
                'activity_code' => $activityCode,
                'required_fields' => $requiredFields ?: null,
                'route_name' => optional($request->route())->getName(),
                'request_path' => $request->path(),
                'request_method' => $request->method(),
                'request_data' => $requestData,
                'client_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'notes' => $notes,
            ]);
        } catch (\Exception $e) {
            // Logging failure should not break the request
            // Silently fail - this is best-effort logging
            \Log::error('Failed to log privileged activity', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'role_type' => $roleType,
                'object_code' => $objectCode,
            ]);
        }
    }
}

