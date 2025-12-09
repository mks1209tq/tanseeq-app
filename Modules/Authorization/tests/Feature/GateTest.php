<?php

use Modules\Authentication\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\AuthObjectField;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\Authorization\Entities\RoleAuthorizationField;
use Pest\Laravel\TestCase;

uses(RefreshDatabase::class);

it('allows access via gate when user has matching authorization', function () {
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

    $result = Gate::forUser($user)->allows('auth-object', ['SALES_ORDER_HEADER', ['ACTVT' => '03']]);

    expect($result)->toBeTrue();
});

it('denies access via gate when user does not have matching authorization', function () {
    $user = User::factory()->create();

    $result = Gate::forUser($user)->allows('auth-object', ['SALES_ORDER_HEADER', ['ACTVT' => '03']]);

    expect($result)->toBeFalse();
});

