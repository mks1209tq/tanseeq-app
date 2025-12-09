<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\ConfigTransports\Contracts\Transportable;

uses(RefreshDatabase::class);

it('implements transportable interface', function () {
    expect(RoleAuthorization::class)->toImplement(Transportable::class);
});

it('returns correct transport object type', function () {
    expect(RoleAuthorization::getTransportObjectType())->toBe('role_authorization');
});

it('returns transport identifier with role and auth object', function () {
    $role = Role::create(['name' => 'TestRole', 'description' => 'Test']);
    $authObject = AuthObject::create(['code' => 'TEST_OBJECT', 'description' => 'Test']);

    $roleAuth = RoleAuthorization::create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
        'label' => 'Test Authorization',
    ]);

    $identifier = $roleAuth->getTransportIdentifier();

    expect($identifier)->toBeArray();
    expect($identifier)->toHaveKey('role_name');
    expect($identifier)->toHaveKey('auth_object_code');
    expect($identifier['role_name'])->toBe('TestRole');
    expect($identifier['auth_object_code'])->toBe('TEST_OBJECT');
});

it('serializes to transport payload', function () {
    $role = Role::create(['name' => 'TestRole', 'description' => 'Test']);
    $authObject = AuthObject::create(['code' => 'TEST_OBJECT', 'description' => 'Test']);

    $roleAuth = RoleAuthorization::create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
        'label' => 'Test Authorization',
    ]);

    $payload = $roleAuth->toTransportPayload();

    expect($payload)->toHaveKey('role_name');
    expect($payload)->toHaveKey('auth_object_code');
    expect($payload)->toHaveKey('label');
});

it('applies transport payload for create operation', function () {
    $role = Role::create(['name' => 'TestRole', 'description' => 'Test']);
    $authObject = AuthObject::create(['code' => 'TEST_OBJECT', 'description' => 'Test']);

    RoleAuthorization::applyTransportPayload([
        'role_name' => 'TestRole',
        'auth_object_code' => 'TEST_OBJECT',
    ], [
        'role_name' => 'TestRole',
        'auth_object_code' => 'TEST_OBJECT',
        'label' => 'New Authorization',
    ], 'create');

    $roleAuth = RoleAuthorization::where('role_id', $role->id)
        ->where('auth_object_id', $authObject->id)
        ->first();

    expect($roleAuth)->not->toBeNull();
    expect($roleAuth->label)->toBe('New Authorization');
});

it('throws exception when role not found', function () {
    $authObject = AuthObject::create(['code' => 'TEST_OBJECT', 'description' => 'Test']);

    expect(fn () => RoleAuthorization::applyTransportPayload([
        'role_name' => 'NON_EXISTENT',
        'auth_object_code' => 'TEST_OBJECT',
    ], [], 'create'))
        ->toThrow(\RuntimeException::class);
});

it('throws exception when auth object not found', function () {
    $role = Role::create(['name' => 'TestRole', 'description' => 'Test']);

    expect(fn () => RoleAuthorization::applyTransportPayload([
        'role_name' => 'TestRole',
        'auth_object_code' => 'NON_EXISTENT',
    ], [], 'create'))
        ->toThrow(\RuntimeException::class);
});

it('returns dependencies including role and auth object', function () {
    $role = Role::create(['name' => 'TestRole', 'description' => 'Test']);
    $authObject = AuthObject::create(['code' => 'TEST_OBJECT', 'description' => 'Test']);

    $roleAuth = RoleAuthorization::create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
        'label' => 'Test Authorization',
    ]);

    $dependencies = $roleAuth->getTransportDependencies();

    expect($dependencies)->toHaveCount(2);
    expect($dependencies[0]['type'])->toBe('role');
    expect($dependencies[0]['identifier'])->toBe('TestRole');
    expect($dependencies[1]['type'])->toBe('auth_object');
    expect($dependencies[1]['identifier'])->toBe('TEST_OBJECT');
});

