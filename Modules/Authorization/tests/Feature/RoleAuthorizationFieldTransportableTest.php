<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\Authorization\Entities\RoleAuthorizationField;
use Modules\ConfigTransports\Contracts\Transportable;

uses(RefreshDatabase::class);

it('implements transportable interface', function () {
    expect(RoleAuthorizationField::class)->toImplement(Transportable::class);
});

it('returns correct transport object type', function () {
    expect(RoleAuthorizationField::getTransportObjectType())->toBe('role_authorization_field');
});

it('returns transport identifier with role, auth object, and field code', function () {
    $role = Role::create(['name' => 'TestRole', 'description' => 'Test']);
    $authObject = AuthObject::create(['code' => 'TEST_OBJECT', 'description' => 'Test']);

    $roleAuth = RoleAuthorization::create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
        'label' => 'Test Authorization',
    ]);

    $field = RoleAuthorizationField::create([
        'role_authorization_id' => $roleAuth->id,
        'field_code' => 'FIELD_CODE',
        'operator' => 'EQ',
        'value_from' => 'VALUE1',
        'value_to' => 'VALUE2',
    ]);

    $identifier = $field->getTransportIdentifier();

    expect($identifier)->toBeArray();
    expect($identifier)->toHaveKey('role_name');
    expect($identifier)->toHaveKey('auth_object_code');
    expect($identifier)->toHaveKey('field_code');
    expect($identifier['role_name'])->toBe('TestRole');
    expect($identifier['auth_object_code'])->toBe('TEST_OBJECT');
    expect($identifier['field_code'])->toBe('FIELD_CODE');
});

it('serializes to transport payload', function () {
    $role = Role::create(['name' => 'TestRole', 'description' => 'Test']);
    $authObject = AuthObject::create(['code' => 'TEST_OBJECT', 'description' => 'Test']);

    $roleAuth = RoleAuthorization::create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
        'label' => 'Test Authorization',
    ]);

    $field = RoleAuthorizationField::create([
        'role_authorization_id' => $roleAuth->id,
        'field_code' => 'FIELD_CODE',
        'operator' => 'EQ',
        'value_from' => 'VALUE1',
        'value_to' => 'VALUE2',
    ]);

    $payload = $field->toTransportPayload();

    expect($payload)->toHaveKey('role_name');
    expect($payload)->toHaveKey('auth_object_code');
    expect($payload)->toHaveKey('field_code');
    expect($payload)->toHaveKey('operator');
    expect($payload)->toHaveKey('value_from');
    expect($payload)->toHaveKey('value_to');
});

it('applies transport payload for create operation', function () {
    $role = Role::create(['name' => 'TestRole', 'description' => 'Test']);
    $authObject = AuthObject::create(['code' => 'TEST_OBJECT', 'description' => 'Test']);

    $roleAuth = RoleAuthorization::create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
        'label' => 'Test Authorization',
    ]);

    RoleAuthorizationField::applyTransportPayload([
        'role_name' => 'TestRole',
        'auth_object_code' => 'TEST_OBJECT',
        'field_code' => 'NEW_FIELD',
    ], [
        'role_name' => 'TestRole',
        'auth_object_code' => 'TEST_OBJECT',
        'field_code' => 'NEW_FIELD',
        'operator' => 'EQ',
        'value_from' => 'VALUE1',
        'value_to' => 'VALUE2',
    ], 'create');

    $field = RoleAuthorizationField::where('role_authorization_id', $roleAuth->id)
        ->where('field_code', 'NEW_FIELD')
        ->first();

    expect($field)->not->toBeNull();
    expect($field->operator)->toBe('EQ');
});

it('throws exception when role authorization not found', function () {
    $role = Role::create(['name' => 'TestRole', 'description' => 'Test']);
    $authObject = AuthObject::create(['code' => 'TEST_OBJECT', 'description' => 'Test']);

    expect(fn () => RoleAuthorizationField::applyTransportPayload([
        'role_name' => 'TestRole',
        'auth_object_code' => 'TEST_OBJECT',
        'field_code' => 'FIELD_CODE',
    ], [], 'create'))
        ->toThrow(\RuntimeException::class);
});

it('returns dependencies including role, auth object, and role authorization', function () {
    $role = Role::create(['name' => 'TestRole', 'description' => 'Test']);
    $authObject = AuthObject::create(['code' => 'TEST_OBJECT', 'description' => 'Test']);

    $roleAuth = RoleAuthorization::create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
        'label' => 'Test Authorization',
    ]);

    $field = RoleAuthorizationField::create([
        'role_authorization_id' => $roleAuth->id,
        'field_code' => 'FIELD_CODE',
        'operator' => 'EQ',
    ]);

    $dependencies = $field->getTransportDependencies();

    expect($dependencies)->toHaveCount(3);
    expect($dependencies[0]['type'])->toBe('role');
    expect($dependencies[1]['type'])->toBe('auth_object');
    expect($dependencies[2]['type'])->toBe('role_authorization');
});

