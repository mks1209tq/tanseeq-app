<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Authorization\Entities\Role;
use Modules\ConfigTransports\Entities\TransportItem;
use Modules\ConfigTransports\Entities\TransportRequest;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['system.environment_role' => 'dev']);
    Storage::fake('local');
});

it('exports a released transport request to json', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'target_environments' => ['qa', 'prod'],
        'description' => 'Test Transport',
        'created_by' => $user->id,
        'released_by' => $user->id,
        'released_at' => now(),
    ]);

    TransportItem::create([
        'transport_request_id' => $transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'create',
        'payload' => ['name' => 'TestRole', 'description' => 'Test'],
    ]);

    $this->artisan('transports:export', ['number' => 'DEVK900001'])
        ->assertSuccessful();

    $filePath = storage_path('app/transports/DEVK900001.json');
    expect(file_exists($filePath))->toBeTrue();

    $data = json_decode(file_get_contents($filePath), true);
    expect($data)->toHaveKey('transport');
    expect($data)->toHaveKey('items');
    expect($data['transport']['number'])->toBe('DEVK900001');
    expect($data['items'])->toHaveCount(1);
});

it('fails to export non-released transport request', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'target_environments' => ['qa', 'prod'],
        'description' => 'Test Transport',
        'created_by' => $user->id,
    ]);

    $this->artisan('transports:export', ['number' => 'DEVK900001'])
        ->assertFailed();
});

it('collapses multiple items for same object into final state', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'target_environments' => ['qa', 'prod'],
        'description' => 'Test Transport',
        'created_by' => $user->id,
        'released_by' => $user->id,
        'released_at' => now(),
    ]);

    // Create multiple items for same role
    TransportItem::create([
        'transport_request_id' => $transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'create',
        'payload' => ['name' => 'TestRole', 'description' => 'Original'],
        'created_at' => now()->subMinutes(2),
    ]);

    TransportItem::create([
        'transport_request_id' => $transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'update',
        'payload' => ['description' => 'Updated'],
        'created_at' => now()->subMinutes(1),
    ]);

    $this->artisan('transports:export', ['number' => 'DEVK900001'])
        ->assertSuccessful();

    $filePath = storage_path('app/transports/DEVK900001.json');
    $data = json_decode(file_get_contents($filePath), true);

    // Should have only one item with merged payload
    expect($data['items'])->toHaveCount(1);
    expect($data['items'][0]['operation'])->toBe('update');
});

it('updates transport request status to exported after export', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'target_environments' => ['qa', 'prod'],
        'description' => 'Test Transport',
        'created_by' => $user->id,
        'released_by' => $user->id,
        'released_at' => now(),
    ]);

    $this->artisan('transports:export', ['number' => 'DEVK900001'])
        ->assertSuccessful();

    expect($transport->fresh()->status)->toBe('exported');
});

it('handles custom export path', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'target_environments' => ['qa', 'prod'],
        'description' => 'Test Transport',
        'created_by' => $user->id,
        'released_by' => $user->id,
        'released_at' => now(),
    ]);

    TransportItem::create([
        'transport_request_id' => $transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'create',
        'payload' => ['name' => 'TestRole'],
    ]);

    $customPath = 'custom/exports/test-transport.json';
    $this->artisan('transports:export', [
        'number' => 'DEVK900001',
        '--path' => $customPath,
    ])->assertSuccessful();

    $filePath = storage_path("app/{$customPath}");
    expect(file_exists($filePath))->toBeTrue();
});

it('handles delete operation in item collapsing', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'target_environments' => ['qa', 'prod'],
        'description' => 'Test Transport',
        'created_by' => $user->id,
        'released_by' => $user->id,
        'released_at' => now(),
    ]);

    // Create then delete
    TransportItem::create([
        'transport_request_id' => $transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'create',
        'payload' => ['name' => 'TestRole'],
        'created_at' => now()->subMinutes(2),
    ]);

    TransportItem::create([
        'transport_request_id' => $transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'delete',
        'payload' => null,
        'created_at' => now()->subMinutes(1),
    ]);

    $this->artisan('transports:export', ['number' => 'DEVK900001'])
        ->assertSuccessful();

    $filePath = storage_path('app/transports/DEVK900001.json');
    $data = json_decode(file_get_contents($filePath), true);

    expect($data['items'])->toHaveCount(1);
    expect($data['items'][0]['operation'])->toBe('delete');
});

