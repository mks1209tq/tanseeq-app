<?php

namespace Modules\Authorization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Authorization\Services\AuthorizationService;
use Modules\Authorization\Services\PrivilegedActivityLogger;
use Symfony\Component\HttpFoundation\Response;

class AuthObjectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $objectCode, ?string $activityCode = null): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthenticated');
        }

        // Build required fields array
        $requiredFields = [];

        // Add activity code if provided
        if ($activityCode !== null) {
            $requiredFields['ACTVT'] = $activityCode;
        }

        // Add fields from route parameters
        // Common parameter names: company, companyCode, salesOrg, sales_org, etc.
        if ($request->route('company')) {
            $requiredFields['COMP_CODE'] = $request->route('company');
        }

        if ($request->route('companyCode')) {
            $requiredFields['COMP_CODE'] = $request->route('companyCode');
        }

        if ($request->route('salesOrg')) {
            $requiredFields['SALES_ORG'] = $request->route('salesOrg');
        }

        if ($request->route('sales_org')) {
            $requiredFields['SALES_ORG'] = $request->route('sales_org');
        }

        // Super-admin completely bypasses all authorization checks
        $authService = app(\App\Contracts\Services\AuthenticationServiceInterface::class);
        if ($authService->isSuperAdmin($user->id)) {
            // Log privileged activity for SuperAdmin
            $logger = app(PrivilegedActivityLogger::class);
            $logger->log(
                $user->id,
                'SuperAdmin',
                $objectCode,
                $activityCode,
                $requiredFields,
                $request,
                'SuperAdmin bypassed authorization check'
            );
            
            return $next($request);
        }

        // Super-read-only bypasses authorization for read-only operations
        // Check if this is a read-only request (GET/HEAD with no activity code or activity = '03')
        $isReadOnlyRequest = in_array($request->method(), ['GET', 'HEAD']);
        $isReadOnlyActivity = ($activityCode === null || $activityCode === '03');
        
        if ($isReadOnlyRequest && $isReadOnlyActivity && $authService->isSuperReadOnly($user->id)) {
            // Log privileged activity for SuperReadOnly
            $logger = app(PrivilegedActivityLogger::class);
            $logger->log(
                $user->id,
                'SuperReadOnly',
                $objectCode,
                $activityCode ?? '03',
                $requiredFields,
                $request,
                'SuperReadOnly bypassed authorization check for read-only operation'
            );
            
            return $next($request);
        }

        // Check authorization for non-super-admin and non-super-read-only users
        $authorizationService = app(AuthorizationService::class);
        if (! $authorizationService->check($user->id, $objectCode, $requiredFields)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}

