<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\AuthObject;
use Modules\ConfigTransports\Contracts\Transportable;

uses(RefreshDatabase::class);

it('implements transportable interface', function () {
    expect(AuthObject::class)->toImplement(Transportable::class);
});

it('returns correct transport object type', function () {
    expect(AuthObject::getTransportObjectType())->toBe('auth_object');
});

it('returns transport identifier', function () {
    $authObject = AuthObject::create([
        'code' => 'TEST_OBJECT',
        'description' => 'Test Object',
    ]);

    expect($authObject->getTransportIdentifier())->toBe('TEST_OBJECT');
});

it('serializes to transport payload', function () {
    $authObject = AuthObject::create([
        'code' => 'TEST_OBJECT',
        'description' => 'Test Description',
    ]);

    $payload = $authObject->toTransportPayload();

    expect($payload)->toHaveKey('code');
    expect($payload)->toHaveKey('description');
    expect($payload)->not->toHaveKey('id');
    expect($payload)->not->toHaveKey('created_at');
});

it('applies transport payload for create operation', function () {
    AuthObject::applyTransportPayload('NEW_OBJECT', [
        'code' => 'NEW_OBJECT',
        'description' => 'New Description',
    ], 'create');

    $authObject = AuthObject::where('code', 'NEW_OBJECT')->first();
    expect($authObject)->not->toBeNull();
    expect($authObject->description)->toBe('New Description');
});

it('applies transport payload for update operation', function () {
    $authObject = AuthObject::create([
        'code' => 'EXISTING_OBJECT',
        'description' => 'Original',
    ]);

    AuthObject::applyTransportPayload('EXISTING_OBJECT', [
        'description' => 'Updated',
    ], 'update');

    $authObject->refresh();
    expect($authObject->description)->toBe('Updated');
});

it('applies transport payload for delete operation', function () {
    $authObject = AuthObject::create([
        'code' => 'TO_DELETE',
        'description' => 'Test',
    ]);

    AuthObject::applyTransportPayload('TO_DELETE', [], 'delete');

    expect(AuthObject::where('code', 'TO_DELETE')->exists())->toBeFalse();
});

it('returns empty dependencies', function () {
    $authObject = AuthObject::create([
        'code' => 'TEST_OBJECT',
        'description' => 'Test',
    ]);

    expect($authObject->getTransportDependencies())->toBe([]);
});

it('is idempotent for create operation', function () {
    AuthObject::applyTransportPayload('IDEMPOTENT_OBJECT', [
        'code' => 'IDEMPOTENT_OBJECT',
        'description' => 'First',
    ], 'create');

    AuthObject::applyTransportPayload('IDEMPOTENT_OBJECT', [
        'code' => 'IDEMPOTENT_OBJECT',
        'description' => 'Second',
    ], 'create');

    $authObject = AuthObject::where('code', 'IDEMPOTENT_OBJECT')->first();
    expect($authObject->description)->toBe('Second');
});

