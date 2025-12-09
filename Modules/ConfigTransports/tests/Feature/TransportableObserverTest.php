<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\Role;
use Modules\ConfigTransports\Entities\TransportItem;
use Modules\ConfigTransports\Entities\TransportRequest;
use Modules\ConfigTransports\Observers\TransportableObserver;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['system.environment_role' => 'dev']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('observes model created event', function () {
    $observer = new TransportableObserver();

    $role = new Role(['name' => 'TestRole', 'description' => 'Test']);
    $role->save();

    $transportRequest = TransportRequest::where('status', 'open')->first();
    expect($transportRequest)->not->toBeNull();

    $item = TransportItem::where('transport_request_id', $transportRequest->id)
        ->where('object_type', 'role')
        ->where('operation', 'create')
        ->first();

    expect($item)->not->toBeNull();
});

it('observes model updated event', function () {
    $role = Role::create([
        'name' => 'TestRole',
        'description' => 'Original',
    ]);

    // Clear initial create item
    TransportItem::truncate();

    $role->update(['description' => 'Updated']);

    $item = TransportItem::where('object_type', 'role')
        ->where('operation', 'update')
        ->first();

    expect($item)->not->toBeNull();
});

it('observes model deleted event', function () {
    $role = Role::create([
        'name' => 'TestRole',
        'description' => 'Test',
    ]);

    $identifier = $role->getTransportIdentifier();
    $role->delete();

    $item = TransportItem::where('object_type', 'role')
        ->where('operation', 'delete')
        ->first();

    expect($item)->not->toBeNull();
    expect($item->identifier)->toBe(['key' => 'TestRole']);
});

it('does not observe in non-dev environments', function () {
    config(['system.environment_role' => 'qa']);

    Role::create([
        'name' => 'TestRole',
        'description' => 'Test',
    ]);

    $transportRequest = TransportRequest::where('status', 'open')->first();
    expect($transportRequest)->toBeNull();
});

it('observes multiple transportable models', function () {
    Role::create(['name' => 'TestRole', 'description' => 'Test']);
    AuthObject::create(['code' => 'TEST_OBJECT', 'description' => 'Test']);

    $transportRequest = TransportRequest::where('status', 'open')->first();
    expect($transportRequest)->not->toBeNull();

    $items = TransportItem::where('transport_request_id', $transportRequest->id)->get();
    expect($items)->toHaveCount(2);
});

