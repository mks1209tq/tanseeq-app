<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\Role;
use Modules\ConfigTransports\Entities\TransportItem;
use Modules\ConfigTransports\Entities\TransportRequest;
use Modules\ConfigTransports\Services\TransportRecorder;
use Modules\Authentication\Entities\User;
use App\Models\Tenant;
use App\Services\TenantService;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['system.environment_role' => 'dev']);
    
    // Set up tenant for all transport tests
    $tenant = Tenant::factory()->create();
    app(TenantService::class)->setCurrentTenant($tenant);
});

it('records create operations in dev environment', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $role = Role::create([
        'name' => 'TestRole',
        'description' => 'Test Description',
    ]);

    $transportRequest = TransportRequest::where('status', 'open')->first();
    expect($transportRequest)->not->toBeNull();

    $item = TransportItem::where('transport_request_id', $transportRequest->id)
        ->where('object_type', 'role')
        ->where('operation', 'create')
        ->first();

    expect($item)->not->toBeNull();
    expect($item->identifier)->toBe(['key' => 'TestRole']);
});

it('records update operations in dev environment', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $role = Role::create([
        'name' => 'TestRole',
        'description' => 'Original Description',
    ]);

    // Clear the initial create item
    TransportItem::truncate();

    $role->update(['description' => 'Updated Description']);

    $item = TransportItem::where('object_type', 'role')
        ->where('operation', 'update')
        ->first();

    expect($item)->not->toBeNull();
});

it('records delete operations in dev environment', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $role = Role::create([
        'name' => 'TestRole',
        'description' => 'Test Description',
    ]);

    $identifier = $role->getTransportIdentifier();
    $role->delete();

    $item = TransportItem::where('object_type', 'role')
        ->where('operation', 'delete')
        ->first();

    expect($item)->not->toBeNull();
    expect($item->identifier)->toBe(['key' => 'TestRole']);
});

it('does not record in non-dev environments', function () {
    config(['system.environment_role' => 'qa']);

    $user = User::factory()->create();
    $this->actingAs($user);

    $role = Role::create([
        'name' => 'TestRole',
        'description' => 'Test Description',
    ]);

    $transportRequest = TransportRequest::where('status', 'open')->first();
    expect($transportRequest)->toBeNull();
});

it('does not record when user is not authenticated', function () {
    Role::create([
        'name' => 'TestRole',
        'description' => 'Test Description',
    ]);

    $transportRequest = TransportRequest::where('status', 'open')->first();
    expect($transportRequest)->toBeNull();
});

