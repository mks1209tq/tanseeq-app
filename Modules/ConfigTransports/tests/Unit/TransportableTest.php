<?php

use Modules\Authorization\Entities\Role;
use Modules\ConfigTransports\Contracts\Transportable;

it('implements transportable interface', function () {
    expect(Role::class)->toImplement(Transportable::class);
});

it('returns correct transport object type', function () {
    expect(Role::getTransportObjectType())->toBe('role');
});

it('returns transport identifier', function () {
    $role = Role::factory()->create(['name' => 'TestRole']);

    expect($role->getTransportIdentifier())->toBe('TestRole');
});

it('serializes to transport payload', function () {
    $role = Role::factory()->create([
        'name' => 'TestRole',
        'description' => 'Test Description',
    ]);

    $payload = $role->toTransportPayload();

    expect($payload)->toHaveKey('name');
    expect($payload)->toHaveKey('description');
    expect($payload)->not->toHaveKey('id');
    expect($payload)->not->toHaveKey('created_at');
    expect($payload)->not->toHaveKey('updated_at');
});

it('applies transport payload for create operation', function () {
    Role::applyTransportPayload('NewRole', ['name' => 'NewRole', 'description' => 'New Description'], 'create');

    $role = Role::where('name', 'NewRole')->first();
    expect($role)->not->toBeNull();
    expect($role->description)->toBe('New Description');
});

it('applies transport payload for update operation', function () {
    $role = Role::factory()->create(['name' => 'ExistingRole', 'description' => 'Original']);

    Role::applyTransportPayload('ExistingRole', ['description' => 'Updated'], 'update');

    $role->refresh();
    expect($role->description)->toBe('Updated');
});

it('applies transport payload for delete operation', function () {
    $role = Role::factory()->create(['name' => 'ToDelete']);

    Role::applyTransportPayload('ToDelete', [], 'delete');

    expect(Role::where('name', 'ToDelete')->exists())->toBeFalse();
});

it('returns empty dependencies for role', function () {
    $role = Role::factory()->create();

    expect($role->getTransportDependencies())->toBe([]);
});

