<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Entities\User;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\Authorization\Entities\RoleAuthorizationField;

beforeEach(function () {
    // Ensure the seeder runs to create all auth objects and roles
    $this->artisan('db:seed', ['--class' => 'Modules\\Authorization\\Database\\Seeders\\AuthorizationDatabaseSeeder']);
});

it('requires authorization middleware on all authenticated routes', function () {
    $routes = Route::getRoutes();
    $authenticatedRoutes = [];
    $routesWithoutAuth = [];

    foreach ($routes as $route) {
        $middleware = $route->gatherMiddleware();
        
        // Skip guest routes and authentication flow routes
        if (in_array('guest', $middleware)) {
            continue;
        }

        // Skip API routes (they use Sanctum)
        if (str_starts_with($route->uri(), 'api/')) {
            continue;
        }

        // Skip health check and other system routes
        if (in_array($route->uri(), ['up', 'sanctum/csrf-cookie'])) {
            continue;
        }

        // Skip authentication flow routes (email verification, password confirmation, logout)
        $authFlowRoutes = [
            'verification.notice',
            'verification.verify',
            'verification.send',
            'password.confirm',
            'logout',
        ];
        if (in_array($route->getName(), $authFlowRoutes) || 
            str_contains($route->uri(), 'verify-email') ||
            str_contains($route->uri(), 'email/verification') ||
            str_contains($route->uri(), 'confirm-password') ||
            $route->uri() === 'logout') {
            continue;
        }

        // Check if route requires authentication
        if (in_array('auth', $middleware) || in_array('auth:sanctum', $middleware)) {
            $authenticatedRoutes[] = [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'methods' => $route->methods(),
                'middleware' => $middleware,
            ];

            // Check if it has auth.object middleware
            $hasAuthObject = false;
            foreach ($middleware as $mw) {
                if (is_string($mw) && str_starts_with($mw, 'auth.object:')) {
                    $hasAuthObject = true;
                    break;
                }
            }

            if (!$hasAuthObject) {
                $routesWithoutAuth[] = [
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'methods' => $route->methods(),
                ];
            }
        }
    }

    if (!empty($routesWithoutAuth)) {
        $message = "The following authenticated routes are missing 'auth.object' middleware:\n\n";
        foreach ($routesWithoutAuth as $route) {
            $message .= sprintf(
                "- %s %s (name: %s)\n",
                implode('|', $route['methods']),
                $route['uri'],
                $route['name'] ?? 'unnamed'
            );
        }
        $message .= "\nAll authenticated routes MUST include 'auth.object:OBJECT_CODE' middleware.";
        
        expect($routesWithoutAuth)->toBeEmpty($message);
    }
});

it('verifies all authorization objects exist in database', function () {
    $routes = Route::getRoutes();
    $authObjects = [];

    foreach ($routes as $route) {
        $middleware = $route->gatherMiddleware();
        
        foreach ($middleware as $mw) {
            if (is_string($mw) && str_starts_with($mw, 'auth.object:')) {
                $objectCode = substr($mw, strlen('auth.object:'));
                // Handle activity codes (e.g., "auth.object:OBJECT,03")
                $objectCode = explode(',', $objectCode)[0];
                if (!in_array($objectCode, $authObjects)) {
                    $authObjects[] = $objectCode;
                }
            }
        }
    }

    $missingObjects = [];
    foreach ($authObjects as $objectCode) {
        $exists = AuthObject::where('code', $objectCode)->exists();
        if (!$exists) {
            $missingObjects[] = $objectCode;
        }
    }

    if (!empty($missingObjects)) {
        $message = "The following authorization objects are referenced in routes but do not exist in the database:\n\n";
        $message .= implode("\n", array_map(fn($code) => "- {$code}", $missingObjects));
        $message .= "\n\nPlease add these objects to the AuthorizationDatabaseSeeder.";
        
        expect($missingObjects)->toBeEmpty($message);
    }
});

it('verifies superadmin role exists', function () {
    $superAdminRole = Role::where('name', 'SuperAdmin')->first();
    
    expect($superAdminRole)->not->toBeNull('SuperAdmin role must exist');
    
    // Note: SuperAdmin completely bypasses the authorization system,
    // so they don't need explicit authorizations. This test just verifies
    // the role exists for the bypass logic to work.
});

it('verifies superreadonly role exists', function () {
    $superReadOnlyRole = Role::where('name', 'SuperReadOnly')->first();
    
    expect($superReadOnlyRole)->not->toBeNull('SuperReadOnly role must exist');
    
    // Note: SuperReadOnly bypasses authorization for read-only operations (ACTVT = '03'),
    // so they don't need explicit authorizations for display operations. This test just verifies
    // the role exists for the bypass logic to work.
});

it('verifies authorization objects have ACTVT field', function () {
    $authObjects = AuthObject::all();
    $objectsWithoutActvt = [];

    foreach ($authObjects as $authObject) {
        $hasActvt = $authObject->fields()
            ->where('code', 'ACTVT')
            ->exists();

        if (!$hasActvt) {
            $objectsWithoutActvt[] = $authObject->code;
        }
    }

    if (!empty($objectsWithoutActvt)) {
        $message = "The following authorization objects are missing the ACTVT field:\n\n";
        $message .= implode("\n", array_map(fn($code) => "- {$code}", $objectsWithoutActvt));
        $message .= "\n\nAll authorization objects should have an ACTVT field for activity-based authorization.";
        
        expect($objectsWithoutActvt)->toBeEmpty($message);
    }
});

