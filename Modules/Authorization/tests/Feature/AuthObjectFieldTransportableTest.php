<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\AuthObjectField;
use Modules\ConfigTransports\Contracts\Transportable;

uses(RefreshDatabase::class);

it('implements transportable interface', function () {
    expect(AuthObjectField::class)->toImplement(Transportable::class);
});

it('returns correct transport object type', function () {
    expect(AuthObjectField::getTransportObjectType())->toBe('auth_object_field');
});

it('returns transport identifier with auth object code', function () {
    $authObject = AuthObject::create([
        'code' => 'TEST_OBJECT',
        'description' => 'Test',
    ]);

    $field = AuthObjectField::create([
        'auth_object_id' => $authObject->id,
        'code' => 'FIELD_CODE',
        'label' => 'Field Label',
    ]);

    $identifier = $field->getTransportIdentifier();

    expect($identifier)->toBeArray();
    expect($identifier)->toHaveKey('auth_object_code');
    expect($identifier)->toHaveKey('code');
    expect($identifier['auth_object_code'])->toBe('TEST_OBJECT');
    expect($identifier['code'])->toBe('FIELD_CODE');
});

it('serializes to transport payload', function () {
    $authObject = AuthObject::create([
        'code' => 'TEST_OBJECT',
        'description' => 'Test',
    ]);

    $field = AuthObjectField::create([
        'auth_object_id' => $authObject->id,
        'code' => 'FIELD_CODE',
        'label' => 'Field Label',
        'is_org_level' => true,
        'sort' => 10,
    ]);

    $payload = $field->toTransportPayload();

    expect($payload)->toHaveKey('auth_object_code');
    expect($payload)->toHaveKey('code');
    expect($payload)->toHaveKey('label');
    expect($payload)->toHaveKey('is_org_level');
    expect($payload)->toHaveKey('sort');
});

it('applies transport payload for create operation', function () {
    $authObject = AuthObject::create([
        'code' => 'TEST_OBJECT',
        'description' => 'Test',
    ]);

    AuthObjectField::applyTransportPayload([
        'auth_object_code' => 'TEST_OBJECT',
        'code' => 'NEW_FIELD',
    ], [
        'auth_object_code' => 'TEST_OBJECT',
        'code' => 'NEW_FIELD',
        'label' => 'New Field',
        'is_org_level' => false,
        'sort' => 5,
    ], 'create');

    $field = AuthObjectField::where('code', 'NEW_FIELD')->first();
    expect($field)->not->toBeNull();
    expect($field->label)->toBe('New Field');
});

it('throws exception when auth object not found', function () {
    expect(fn () => AuthObjectField::applyTransportPayload([
        'auth_object_code' => 'NON_EXISTENT',
        'code' => 'FIELD_CODE',
    ], [], 'create'))
        ->toThrow(\RuntimeException::class);
});

it('returns dependencies including auth object', function () {
    $authObject = AuthObject::create([
        'code' => 'TEST_OBJECT',
        'description' => 'Test',
    ]);

    $field = AuthObjectField::create([
        'auth_object_id' => $authObject->id,
        'code' => 'FIELD_CODE',
        'label' => 'Field Label',
    ]);

    $dependencies = $field->getTransportDependencies();

    expect($dependencies)->toHaveCount(1);
    expect($dependencies[0]['type'])->toBe('auth_object');
    expect($dependencies[0]['identifier'])->toBe('TEST_OBJECT');
});

