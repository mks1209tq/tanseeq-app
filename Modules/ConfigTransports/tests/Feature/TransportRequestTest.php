<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ConfigTransports\Entities\TransportItem;
use Modules\ConfigTransports\Entities\TransportRequest;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can create a transport request', function () {
    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'target_environments' => ['qa', 'prod'],
        'description' => 'Test Transport',
        'created_by' => $this->user->id,
    ]);

    expect($transport->number)->toBe('DEVK900001');
    expect($transport->type)->toBe('security');
    expect($transport->status)->toBe('open');
    expect($transport->target_environments)->toBe(['qa', 'prod']);
});

it('has relationship with items', function () {
    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    $item = TransportItem::create([
        'transport_request_id' => $transport->id,
        'object_type' => 'role',
        'identifier' => ['key' => 'TestRole'],
        'operation' => 'create',
        'payload' => ['name' => 'TestRole'],
    ]);

    expect($transport->items)->toHaveCount(1);
    expect($transport->items->first()->id)->toBe($item->id);
});

it('has relationship with creator', function () {
    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    expect($transport->creator->id)->toBe($this->user->id);
});

it('has relationship with releaser', function () {
    $releaser = User::factory()->create();

    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
        'released_by' => $releaser->id,
        'released_at' => now(),
    ]);

    expect($transport->releaser->id)->toBe($releaser->id);
});

it('can scope open transport requests', function () {
    TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    TransportRequest::create([
        'number' => 'DEVK900002',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    $openTransports = TransportRequest::open()->get();

    expect($openTransports)->toHaveCount(1);
    expect($openTransports->first()->number)->toBe('DEVK900001');
});

it('can scope released transport requests', function () {
    TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    TransportRequest::create([
        'number' => 'DEVK900002',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    $releasedTransports = TransportRequest::released()->get();

    expect($releasedTransports)->toHaveCount(1);
    expect($releasedTransports->first()->number)->toBe('DEVK900002');
});

it('can scope by type', function () {
    TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    TransportRequest::create([
        'number' => 'DEVK900002',
        'type' => 'config',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    $securityTransports = TransportRequest::forType('security')->get();

    expect($securityTransports)->toHaveCount(1);
    expect($securityTransports->first()->type)->toBe('security');
});

it('checks if can be released', function () {
    $openTransport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    $releasedTransport = TransportRequest::create([
        'number' => 'DEVK900002',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    expect($openTransport->canBeReleased())->toBeTrue();
    expect($releasedTransport->canBeReleased())->toBeFalse();
});

it('checks if can be exported', function () {
    $openTransport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    $releasedTransport = TransportRequest::create([
        'number' => 'DEVK900002',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    expect($openTransport->canBeExported())->toBeFalse();
    expect($releasedTransport->canBeExported())->toBeTrue();
});

it('can release a transport request', function () {
    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    $transport->release($this->user->id);

    expect($transport->fresh()->status)->toBe('released');
    expect($transport->fresh()->released_by)->toBe($this->user->id);
    expect($transport->fresh()->released_at)->not->toBeNull();
});

it('throws exception when releasing non-open transport', function () {
    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
    ]);

    expect(fn () => $transport->release($this->user->id))
        ->toThrow(\RuntimeException::class);
});

it('casts target_environments to array', function () {
    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'open',
        'source_environment' => 'dev',
        'target_environments' => ['qa', 'prod'],
        'created_by' => $this->user->id,
    ]);

    expect($transport->target_environments)->toBeArray();
    expect($transport->target_environments)->toBe(['qa', 'prod']);
});

it('casts released_at to datetime', function () {
    $releasedAt = now();

    $transport = TransportRequest::create([
        'number' => 'DEVK900001',
        'type' => 'security',
        'status' => 'released',
        'source_environment' => 'dev',
        'created_by' => $this->user->id,
        'released_by' => $this->user->id,
        'released_at' => $releasedAt,
    ]);

    expect($transport->released_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

