<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ConfigTransports\Entities\TransportItem;
use Modules\ConfigTransports\Entities\TransportRequest;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    $this->transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);
});

it('can create a transport item', function () {
    $item = TransportItem::create([
        'transport_request_id' => $this->transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'create',
        'payload' => ['name' => 'TestRole', 'description' => 'Test'],
    ]);

    expect($item->object_type)->toBe('role');
    expect($item->operation)->toBe('create');
    expect($item->identifier)->toBe(['key' => 'TestRole']);
    expect($item->payload)->toBe(['name' => 'TestRole', 'description' => 'Test']);
});

it('has relationship with transport request', function () {
    $item = TransportItem::create([
        'transport_request_id' => $this->transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'create',
        'payload' => ['name' => 'TestRole'],
    ]);

    expect($item->transportRequest->id)->toBe($this->transport->id);
});

it('casts identifier to array', function () {
    $item = TransportItem::create([
        'transport_request_id' => $this->transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'create',
        'payload' => ['name' => 'TestRole'],
    ]);

    expect($item->identifier)->toBeArray();
    expect($item->identifier)->toBe(['key' => 'TestRole']);
});

it('casts payload to array', function () {
    $item = TransportItem::create([
        'transport_request_id' => $this->transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'create',
        'payload' => ['name' => 'TestRole', 'description' => 'Test'],
    ]);

    expect($item->payload)->toBeArray();
    expect($item->payload)->toHaveKey('name');
    expect($item->payload)->toHaveKey('description');
});

it('casts meta to array', function () {
    $item = TransportItem::create([
        'transport_request_id' => $this->transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'create',
        'payload' => ['name' => 'TestRole'],
        'meta' => ['recorded_at' => now()->toIso8601String()],
    ]);

    expect($item->meta)->toBeArray();
    expect($item->meta)->toHaveKey('recorded_at');
});

it('can handle different operation types', function () {
    $operations = ['create', 'update', 'delete'];

    foreach ($operations as $operation) {
        $item = TransportItem::create([
            'transport_request_id' => $this->transport->id,
            'object_type' => 'role',
            'identifier' => ['key' => 'TestRole'],
            'operation' => $operation,
            'payload' => $operation === 'delete' ? null : ['name' => 'TestRole'],
        ]);

        expect($item->operation)->toBe($operation);
    }
});

it('can handle complex identifier structures', function () {
    $item = TransportItem::create([
        'transport_request_id' => $this->transport->id,
        'object_type' => 'role_authorization',
        'identifier' => [
            'role_name' => 'TestRole',
            'auth_object_code' => 'TEST_OBJECT',
        ],
        'operation' => 'create',
        'payload' => ['label' => 'Test'],
    ]);

    expect($item->identifier)->toBe([
        'role_name' => 'TestRole',
        'auth_object_code' => 'TEST_OBJECT',
    ]);
});

