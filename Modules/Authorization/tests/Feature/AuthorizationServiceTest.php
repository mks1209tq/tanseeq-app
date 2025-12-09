<?php

use Modules\Authentication\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\AuthObjectField;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\Authorization\Entities\RoleAuthorizationField;
use Modules\Authorization\Services\AuthorizationService;
use Pest\Laravel\TestCase;

uses(RefreshDatabase::class);

it('returns false when user has no roles', function () {
    $user = User::factory()->create();
    $service = app(AuthorizationService::class);

    $result = $service->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03']);

    expect($result)->toBeFalse();
});

it('returns true when user has matching authorization with wildcard operator', function () {
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

    $service = app(AuthorizationService::class);

    $result = $service->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03']);

    expect($result)->toBeTrue();
});

it('returns true when user has matching authorization with equals operator', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'SALES_CLERK']);
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
});

it('returns false when user has authorization with non-matching equals operator', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'SALES_CLERK']);
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
        'value_from' => '02',
    ]);

    $service = app(AuthorizationService::class);

    $result = $service->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03']);

    expect($result)->toBeFalse();
});

it('returns true when user has matching authorization with in operator', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'SALES_CLERK']);
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
        'operator' => 'in',
        'value_from' => '02,03,06',
    ]);

    $service = app(AuthorizationService::class);

    $result = $service->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03']);

    expect($result)->toBeTrue();
});

it('returns true when user has matching authorization with between operator', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'SALES_CLERK']);
    $user->roles()->attach($role);

    $authObject = AuthObject::factory()->create(['code' => 'SALES_ORDER_HEADER']);
    AuthObjectField::factory()->create(['auth_object_id' => $authObject->id, 'code' => 'SALES_ORG']);

    $roleAuth = RoleAuthorization::factory()->create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
    ]);

    RoleAuthorizationField::factory()->create([
        'role_authorization_id' => $roleAuth->id,
        'field_code' => 'SALES_ORG',
        'operator' => 'between',
        'value_from' => '2000',
        'value_to' => '2005',
    ]);

    $service = app(AuthorizationService::class);

    $result = $service->check($user, 'SALES_ORDER_HEADER', ['SALES_ORG' => '2003']);

    expect($result)->toBeTrue();
});

it('caches authorization checks', function () {
    Cache::flush();

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

    $service = app(AuthorizationService::class);

    // First call
    $result1 = $service->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03']);
    expect($result1)->toBeTrue();

    // Verify cache key exists
    $cacheKey = "auth:{$user->id}:SALES_ORDER_HEADER";
    expect(Cache::has($cacheKey))->toBeTrue();
});

it('returns true when at least one authorization matches', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'SALES_CLERK']);
    $user->roles()->attach($role);

    $authObject = AuthObject::factory()->create(['code' => 'SALES_ORDER_HEADER']);
    AuthObjectField::factory()->create(['auth_object_id' => $authObject->id, 'code' => 'ACTVT']);

    // First authorization - doesn't match
    $roleAuth1 = RoleAuthorization::factory()->create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
    ]);

    RoleAuthorizationField::factory()->create([
        'role_authorization_id' => $roleAuth1->id,
        'field_code' => 'ACTVT',
        'operator' => '=',
        'value_from' => '02',
    ]);

    // Second authorization - matches
    $roleAuth2 = RoleAuthorization::factory()->create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
    ]);

    RoleAuthorizationField::factory()->create([
        'role_authorization_id' => $roleAuth2->id,
        'field_code' => 'ACTVT',
        'operator' => '=',
        'value_from' => '03',
    ]);

    $service = app(AuthorizationService::class);

    $result = $service->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03']);

    expect($result)->toBeTrue();
});

