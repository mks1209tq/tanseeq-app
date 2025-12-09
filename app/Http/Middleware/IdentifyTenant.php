<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tenant identification for tenant management routes (admin only)
        if ($request->is('admin/tenants*')) {
            return $next($request);
        }

        // Check if system connection is configured
        if (!config('database.connections.system')) {
            // If system connection is not configured, skip tenant identification
            // This allows the app to work without multi-tenancy if needed
            return $next($request);
        }

        // Immediately configure default tenant connection for sessions
        // This ensures sessions can access the database before tenant is fully identified
        $defaultTenantId = config('tenancy.default_tenant_id', 1);
        if ($defaultTenantId && app()->environment('local', 'testing')) {
            try {
                $defaultTenant = \App\Models\Tenant::where('id', $defaultTenantId)->active()->first();
                if ($defaultTenant) {
                    app(TenantService::class)->setCurrentTenant($defaultTenant);
                }
            } catch (\Exception $e) {
                // Ignore - will try again below
            }
        }

        try {
            $tenantService = app(TenantService::class);
            $tenant = $tenantService->getCurrentTenant();

            if (! $tenant) {
                // Try to use default tenant for development
                $defaultTenantId = config('tenancy.default_tenant_id');
                if ($defaultTenantId && app()->environment('local', 'testing')) {
                    $tenant = \App\Models\Tenant::where('id', $defaultTenantId)->active()->first();
                    if ($tenant) {
                        $tenantService->setCurrentTenant($tenant);
                        return $next($request);
                    }
                }

                // For API/AJAX requests, return JSON error
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'Tenant not found or inactive',
                    ], 404);
                }

                // For web requests, show tenant selection or error page
                abort(404, 'Tenant not found');
            }

            // Set tenant and configure connections
            $tenantService->setCurrentTenant($tenant);
        } catch (\Exception $e) {
            // Log error but don't break the application
            \Log::warning('Error in tenant identification: ' . $e->getMessage());
            
            // For development, try default tenant before giving up
            if (app()->environment('local', 'testing')) {
                $defaultTenantId = config('tenancy.default_tenant_id');
                if ($defaultTenantId) {
                    try {
                        $tenant = \App\Models\Tenant::where('id', $defaultTenantId)->active()->first();
                        if ($tenant) {
                            app(TenantService::class)->setCurrentTenant($tenant);
                            return $next($request);
                        }
                    } catch (\Exception $e2) {
                        \Log::warning('Error setting default tenant: ' . $e2->getMessage());
                    }
                }
                
                // Allow requests to continue without tenant in development
                return $next($request);
            }

            // For production, abort
            abort(500, 'Tenant identification failed');
        }

        return $next($request);
    }
}

