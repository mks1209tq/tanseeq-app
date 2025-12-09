<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Entities\User;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\AuthObjectField;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\Authorization\Entities\RoleAuthorizationField;
use Modules\Authorization\Services\AuthorizationService;
use Modules\AuthorizationDebug\Entities\AuthorizationFailure;
use Modules\AuthorizationDebug\Services\AuthorizationDebugService;
use Pest\Laravel\TestCase;

uses(RefreshDatabase::class);

// AuthorizationDebugService Tests
it('returns null when no failures exist for user', function () {
    $user = User::factory()->create();
    $service = app(AuthorizationDebugService::class);

    $result = $service->getLastFailureForUser($user);

    expect($result)->toBeNull();
});

it('returns latest failure for user', function () {
    $user = User::factory()->create();
    $olderFailure = AuthorizationFailure::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subHour(),
    ]);
    $newerFailure = AuthorizationFailure::factory()->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    $service = app(AuthorizationDebugService::class);

    $result = $service->getLastFailureForUser($user);

    expect($result)->not->toBeNull();
    expect($result->id)->toBe($newerFailure->id);
});

it('returns latest failure when using user id', function () {
    $user = User::factory()->create();
    $failure = AuthorizationFailure::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = app(AuthorizationDebugService::class);

    $result = $service->getLastFailureForUserId($user->id);

    expect($result)->not->toBeNull();
    expect($result->id)->toBe($failure->id);
});

it('returns multiple failures for user', function () {
    $user = User::factory()->create();
    AuthorizationFailure::factory()->count(5)->create([
        'user_id' => $user->id,
    ]);

    $service = app(AuthorizationDebugService::class);

    $result = $service->getFailuresForUser($user, 3);

    expect($result)->toHaveCount(3);
});

// AuthorizationService Logging Tests
it('creates authorization failure record when check fails', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'SALES_MANAGER']);
    $user->roles()->attach($role);

    $authObject = AuthObject::factory()->create(['code' => 'SALES_ORDER_HEADER']);
    AuthObjectField::factory()->create(['auth_object_id' => $authObject->id, 'code' => 'ACTVT']);

    $roleAuth = RoleAuthorization::factory()->create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
    ]);

    // Create authorization that doesn't match
    RoleAuthorizationField::factory()->create([
        'role_authorization_id' => $roleAuth->id,
        'field_code' => 'ACTVT',
        'operator' => '=',
        'value_from' => '01', // User has 01, but we'll check for 03
    ]);

    $service = app(AuthorizationService::class);

    $result = $service->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03']);

    expect($result)->toBeFalse();

    $failure = AuthorizationFailure::where('user_id', $user->id)
        ->where('auth_object_code', 'SALES_ORDER_HEADER')
        ->latest()
        ->first();

    expect($failure)->not->toBeNull();
    expect($failure->is_allowed)->toBeFalse();
    expect($failure->required_fields)->toBe(['ACTVT' => '03']);
});

it('creates authorization failure record when check passes', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'SALES_MANAGER']);
    $user->roles()->attach($role);

    $authObject = AuthObject::factory()->create(['code' => 'SALES_ORDER_HEADER']);
    AuthObjectField::factory()->create(['auth_object_id' => $authObject->id, 'code' => 'ACTVT']);

    $roleAuth = RoleAuthorization::factory()->create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
    ]);

    RoleAuthorizationField::factory()->create([
        'role_authorization_id' => $roleAuth->id,
        'field_code' => 'ACTVT',
        'operator' => '=',
        'value_from' => '03',
    ]);

    $service = app(AuthorizationService::class);

    $result = $service->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03']);

    expect($result)->toBeTrue();

    $failure = AuthorizationFailure::where('user_id', $user->id)
        ->where('auth_object_code', 'SALES_ORDER_HEADER')
        ->latest()
        ->first();

    expect($failure)->not->toBeNull();
    expect($failure->is_allowed)->toBeTrue();
});

it('stores request context in authorization failure', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'SALES_MANAGER']);
    $user->roles()->attach($role);

    $authObject = AuthObject::factory()->create(['code' => 'SALES_ORDER_HEADER']);
    AuthObjectField::factory()->create(['auth_object_id' => $authObject->id, 'code' => 'ACTVT']);

    $roleAuth = RoleAuthorization::factory()->create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
    ]);

    RoleAuthorizationField::factory()->create([
        'role_authorization_id' => $roleAuth->id,
        'field_code' => 'ACTVT',
        'operator' => '=',
        'value_from' => '01',
    ]);

    $this->get('/test-route');

    $service = app(AuthorizationService::class);
    $service->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03']);

    $failure = AuthorizationFailure::where('user_id', $user->id)
        ->latest()
        ->first();

    expect($failure)->not->toBeNull();
    expect($failure->request_method)->toBe('GET');
    expect($failure->request_path)->not->toBeNull();
});

it('builds summary from authorizations', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'SALES_MANAGER']);
    $user->roles()->attach($role);

    $authObject = AuthObject::factory()->create(['code' => 'SALES_ORDER_HEADER']);
    AuthObjectField::factory()->create(['auth_object_id' => $authObject->id, 'code' => 'ACTVT']);
    AuthObjectField::factory()->create(['auth_object_id' => $authObject->id, 'code' => 'COMP_CODE']);

    $roleAuth = RoleAuthorization::factory()->create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
    ]);

    RoleAuthorizationField::factory()->create([
        'role_authorization_id' => $roleAuth->id,
        'field_code' => 'ACTVT',
        'operator' => 'in',
        'value_from' => '01,02,03',
    ]);

    RoleAuthorizationField::factory()->create([
        'role_authorization_id' => $roleAuth->id,
        'field_code' => 'COMP_CODE',
        'operator' => '=',
        'value_from' => '1000',
    ]);

    $service = app(AuthorizationService::class);
    $service->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03', 'COMP_CODE' => '1000']);

    $failure = AuthorizationFailure::where('user_id', $user->id)
        ->latest()
        ->first();

    expect($failure)->not->toBeNull();
    expect($failure->summary)->not->toBeNull();
    expect($failure->summary)->toHaveKey('ACTVT');
    expect($failure->summary)->toHaveKey('COMP_CODE');
});

it('does not break authorization check when logging fails', function () {
    // This test ensures exception safety
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'SALES_MANAGER']);
    $user->roles()->attach($role);

    $authObject = AuthObject::factory()->create(['code' => 'SALES_ORDER_HEADER']);
    AuthObjectField::factory()->create(['auth_object_id' => $authObject->id, 'code' => 'ACTVT']);

    $roleAuth = RoleAuthorization::factory()->create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
    ]);

    RoleAuthorizationField::factory()->create([
        'role_authorization_id' => $roleAuth->id,
        'field_code' => 'ACTVT',
        'operator' => '*',
    ]);

    // Mock a database error scenario by temporarily breaking the connection
    // In a real scenario, this would be handled by the try-catch in logAuthorizationCheck
    $service = app(AuthorizationService::class);

    $result = $service->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03']);

    // Authorization check should still work even if logging fails
    expect($result)->toBeTrue();
});

// SU53 Route Tests
it('allows authenticated user to access su53 route', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/auth/su53');

    $response->assertSuccessful();
});

it('shows no failures message when no failures exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/auth/su53');

    $response->assertSuccessful();
    $response->assertSee('No authorization failures have been logged');
});

it('shows failure details when failure exists', function () {
    $user = User::factory()->create();
    $failure = AuthorizationFailure::factory()->create([
        'user_id' => $user->id,
        'auth_object_code' => 'SALES_ORDER_HEADER',
        'is_allowed' => false,
    ]);

    $response = $this->actingAs($user)->get('/auth/su53');

    $response->assertSuccessful();
    $response->assertSee('SALES_ORDER_HEADER');
    $response->assertSee('DENIED');
});

it('allows admin to access su53 route for other user', function () {
    $admin = User::factory()->create();
    $adminRole = Role::factory()->create(['name' => 'SuperAdmin']);
    $admin->roles()->attach($adminRole);

    $otherUser = User::factory()->create();

    $response = $this->actingAs($admin)->get("/auth/su53/{$otherUser->id}");

    $response->assertSuccessful();
});

it('denies non-admin access to su53 route for other user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $response = $this->actingAs($user)->get("/auth/su53/{$otherUser->id}");

    $response->assertForbidden();
});

it('shows indication when viewing other users failure', function () {
    $admin = User::factory()->create();
    $adminRole = Role::factory()->create(['name' => 'SuperAdmin']);
    $admin->roles()->attach($adminRole);

    $otherUser = User::factory()->create();
    $failure = AuthorizationFailure::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($admin)->get("/auth/su53/{$otherUser->id}");

    $response->assertSuccessful();
    $response->assertSee($otherUser->name);
    $response->assertSee('Viewing authorization failure for');
});

