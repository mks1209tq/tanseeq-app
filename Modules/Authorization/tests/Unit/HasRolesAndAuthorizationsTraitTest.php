<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Entities\User;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\AuthObjectField;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\Authorization\Entities\RoleAuthorizationField;
use Modules\Authorization\Services\AuthorizationService;

uses(RefreshDatabase::class);

it('has roles relationship', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'TestRole']);

    $user->roles()->attach($role);

    expect($user->roles)->toHaveCount(1);
    expect($user->roles->first()->name)->toBe('TestRole');
});

it('checks if user has a role', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'TestRole']);

    $user->roles()->attach($role);

    expect($user->hasRole('TestRole'))->toBeTrue();
    expect($user->hasRole('NonExistentRole'))->toBeFalse();
});

it('checks if user has any of multiple roles', function () {
    $user = User::factory()->create();
    $role1 = Role::factory()->create(['name' => 'Role1']);
    $role2 = Role::factory()->create(['name' => 'Role2']);

    $user->roles()->attach($role1);

    expect($user->hasRole(['Role1', 'Role2']))->toBeTrue();
    expect($user->hasRole(['Role3', 'Role4']))->toBeFalse();
});

it('checks if user has authorization for object', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'TestRole']);
    $user->roles()->attach($role);

    $authObject = AuthObject::factory()->create(['code' => 'TEST_OBJECT']);
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

    expect($user->hasAuthObject('TEST_OBJECT', ['ACTVT' => '03']))->toBeTrue();
    expect($user->hasAuthObject('NON_EXISTENT', ['ACTVT' => '03']))->toBeFalse();
});

it('checks if user is super admin', function () {
    $user = User::factory()->create();
    $superAdminRole = Role::factory()->create(['name' => 'SuperAdmin']);
    $user->roles()->attach($superAdminRole);

    expect($user->isSuperAdmin())->toBeTrue();
});

it('checks super admin with different case variations', function () {
    $variations = ['SuperAdmin', 'super-admin', 'SUPER_ADMIN'];

    foreach ($variations as $variation) {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => $variation]);
        $user->roles()->attach($role);

        expect($user->isSuperAdmin())->toBeTrue();
    }
});

it('returns false when user is not super admin', function () {
    $user = User::factory()->create();
    $regularRole = Role::factory()->create(['name' => 'RegularRole']);
    $user->roles()->attach($regularRole);

    expect($user->isSuperAdmin())->toBeFalse();
});

