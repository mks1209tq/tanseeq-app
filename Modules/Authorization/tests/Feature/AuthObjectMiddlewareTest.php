<?php

use Modules\Authentication\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\AuthObjectField;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\Authorization\Entities\RoleAuthorizationField;
use Pest\Laravel\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(['auth', 'auth.object:SALES_ORDER_HEADER,03'])->get('/test-route', function () {
        return response()->json(['message' => 'success']);
    });
});

it('allows access when user has matching authorization', function () {
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

    $response = $this->actingAs($user)->get('/test-route');

    $response->assertSuccessful();
});

it('denies access when user does not have matching authorization', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/test-route');

    $response->assertForbidden();
});

it('denies access when user is not authenticated', function () {
    $response = $this->get('/test-route');

    $response->assertRedirect('/login');
});

