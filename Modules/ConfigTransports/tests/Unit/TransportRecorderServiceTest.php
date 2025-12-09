<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\Role;
use Modules\ConfigTransports\Entities\TransportItem;
use Modules\ConfigTransports\Entities\TransportRequest;
use Modules\ConfigTransports\Services\TransportRecorder;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['system.environment_role' => 'dev']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->recorder = app(TransportRecorder::class);
});

it('generates transport numbers correctly', function () {
    $reflection = new \ReflectionClass($this->recorder);
    $method = $reflection->getMethod('generateTransportNumber');
    $method->setAccessible(true);

    $number1 = $method->invoke($this->recorder);
    expect($number1)->toMatch('/^DEVK\d{6}$/');

    TransportRequest::create([
        'number' => $number1,
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    $number2 = $method->invoke($this->recorder);
    expect($number2)->not->toBe($number1);
});

it('determines transport type for model', function () {
    $reflection = new \ReflectionClass($this->recorder);
    $method = $reflection->getMethod('getTransportTypeForModel');
    $method->setAccessible(true);

    $role = new Role(['name' => 'TestRole']);
    $type = $method->invoke($this->recorder, $role);

    expect($type)->toBe('security');
});

it('creates open transport request when none exists', function () {
    $role = new Role(['name' => 'TestRole']);
    $request = $this->recorder->getActiveRequest($role);

    expect($request)->not->toBeNull();
    expect($request->status)->toBe('open');
    expect($request->type)->toBe('security');
});

it('returns existing open transport request', function () {
    $existingRequest = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    $role = new Role(['name' => 'TestRole']);
    $request = $this->recorder->getActiveRequest($role);

    expect($request->id)->toBe($existingRequest->id);
});

it('records create operation with correct data', function () {
    $role = Role::create([
        'name' => 'TestRole',
        'description' => 'Test Description',
    ]);

    $request = TransportRequest::where('status', 'open')->first();
    $item = TransportItem::where('transport_request_id', $request->id)
        ->where('object_type', 'role')
        ->where('operation', 'create')
        ->first();

    expect($item)->not->toBeNull();
    expect($item->payload)->toHaveKey('name');
    expect($item->payload)->toHaveKey('description');
    expect($item->meta)->toHaveKey('recorded_at');
    expect($item->meta)->toHaveKey('recorded_by');
});

it('does not record when should not record', function () {
    config(['system.environment_role' => 'qa']);

    $reflection = new \ReflectionClass($this->recorder);
    $method = $reflection->getMethod('shouldRecord');
    $method->setAccessible(true);

    $shouldRecord = $method->invoke($this->recorder);

    expect($shouldRecord)->toBeFalse();
});

