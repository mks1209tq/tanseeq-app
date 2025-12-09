<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ConfigTransports\Entities\TransportItem;
use Modules\ConfigTransports\Entities\TransportRequest;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    config(['system.environment_role' => 'dev']);
});

it('can view transport requests index', function () {
    TransportRequest::factory()->count(3)->create([
        'created_by' => $this->user->id,
    ]);

    $response = $this->get(route('admin.transports.index'));

    $response->assertSuccessful();
    $response->assertViewIs('config-transports::admin.index');
});

it('can view create transport request form', function () {
    $response = $this->get(route('admin.transports.create'));

    $response->assertSuccessful();
    $response->assertViewIs('config-transports::admin.create');
});

it('can create a transport request', function () {
    $response = $this->post(route('admin.transports.store'), [
        'type' => 'security',
        'description' => 'Test Transport',
        'target_environments' => ['qa', 'prod'],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $transport = TransportRequest::where('type', 'security')->first();
    expect($transport)->not->toBeNull();
    expect($transport->description)->toBe('Test Transport');
    expect($transport->target_environments)->toBe(['qa', 'prod']);
});

it('validates required fields when creating transport request', function () {
    $response = $this->post(route('admin.transports.store'), []);

    $response->assertSessionHasErrors(['type']);
});

it('validates type is in allowed values', function () {
    $response = $this->post(route('admin.transports.store'), [
        'type' => 'invalid',
    ]);

    $response->assertSessionHasErrors(['type']);
});

it('can view a transport request', function () {
    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    TransportItem::create([
        'transport_request_id' => $transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'create',
        'payload' => ['name' => 'TestRole'],
    ]);

    $response = $this->get(route('admin.transports.show', $transport));

    $response->assertSuccessful();
    $response->assertViewIs('config-transports::admin.show');
    $response->assertViewHas('transportRequest', $transport);
});

it('can release a transport request', function () {
    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    $response = $this->post(route('admin.transports.release', $transport));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($transport->fresh()->status)->toBe('released');
    expect($transport->fresh()->released_by)->toBe($this->user->id);
    expect($transport->fresh()->released_at)->not->toBeNull();
});

it('requires authentication to access transport requests', function () {
    auth()->logout();

    $response = $this->get(route('admin.transports.index'));

    $response->assertRedirect(route('login'));
});

