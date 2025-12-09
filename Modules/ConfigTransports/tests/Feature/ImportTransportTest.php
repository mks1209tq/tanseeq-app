<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\Role;
use Modules\ConfigTransports\Entities\TransportImportLog;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['system.environment_role' => 'qa']);
});

it('imports a transport request successfully', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $jsonData = [
        'transport' => [
            'number' => 'DEVK900001',
            'type' => 'security',
            'description' => 'Test Transport',
            'source_environment' => 'dev',
            'target_environments' => ['qa', 'prod'],
            'created_by' => 1,
            'released_by' => 1,
            'released_at' => now()->toIso8601String(),
        ],
        'items' => [
            [
                'object_type' => 'role',
                'identifier' => ['key' => 'TestRole'],
                'operation' => 'create',
                'payload' => ['name' => 'TestRole', 'description' => 'Test Description'],
                'meta' => [],
            ],
        ],
    ];

    $filePath = storage_path('app/transports/test-import.json');
    $directory = dirname($filePath);
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    file_put_contents($filePath, json_encode($jsonData, JSON_PRETTY_PRINT));

    // Register handler for role
    $this->app->bind(\Modules\ConfigTransports\Console\Commands\ImportTransportCommand::class, function ($app) {
        $command = new \Modules\ConfigTransports\Console\Commands\ImportTransportCommand();
        // Use reflection to set handlers
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('handlers');
        $property->setAccessible(true);
        $property->setValue($command, ['role' => \Modules\Authorization\Entities\Role::class]);

        return $command;
    });

    $this->artisan('transports:import', ['path' => $filePath])
        ->assertSuccessful();

    // Verify role was created
    $role = Role::where('name', 'TestRole')->first();
    expect($role)->not->toBeNull();
    expect($role->description)->toBe('Test Description');

    // Verify import log was created
    $log = TransportImportLog::where('transport_number', 'DEVK900001')->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe('success');
});

it('fails to import in dev environment without force flag', function () {
    config(['system.environment_role' => 'dev']);

    $filePath = storage_path('app/transports/test-import.json');
    file_put_contents($filePath, json_encode(['transport' => [], 'items' => []]));

    $this->artisan('transports:import', ['path' => $filePath])
        ->assertFailed();
});

it('creates import log with summary', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $jsonData = [
        'transport' => [
            'number' => 'DEVK900001',
            'type' => 'security',
            'description' => 'Test Transport',
            'source_environment' => 'dev',
            'target_environments' => ['qa', 'prod'],
            'created_by' => 1,
            'released_by' => 1,
            'released_at' => now()->toIso8601String(),
        ],
        'items' => [
            [
                'object_type' => 'role',
                'identifier' => ['key' => 'TestRole'],
                'operation' => 'create',
                'payload' => ['name' => 'TestRole', 'description' => 'Test'],
                'meta' => [],
            ],
        ],
    ];

    $filePath = storage_path('app/transports/test-import.json');
    $directory = dirname($filePath);
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    file_put_contents($filePath, json_encode($jsonData, JSON_PRETTY_PRINT));

    $this->artisan('transports:import', ['path' => $filePath, '--force' => true])
        ->assertSuccessful();

    $log = TransportImportLog::where('transport_number', 'DEVK900001')->first();
    expect($log)->not->toBeNull();
    expect($log->summary)->toHaveKey('total');
    expect($log->summary)->toHaveKey('success');
});

it('fails when transport file does not exist', function () {
    $this->artisan('transports:import', ['path' => 'non-existent-file.json'])
        ->assertFailed();
});

it('fails when transport file has invalid format', function () {
    $filePath = storage_path('app/transports/invalid.json');
    $directory = dirname($filePath);
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    file_put_contents($filePath, 'invalid json');

    $this->artisan('transports:import', ['path' => $filePath])
        ->assertFailed();
});

it('handles import with multiple items', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $jsonData = [
        'transport' => [
            'number' => 'DEVK900001',
            'type' => 'security',
            'description' => 'Test Transport',
            'source_environment' => 'dev',
            'target_environments' => ['qa', 'prod'],
            'created_by' => 1,
            'released_by' => 1,
            'released_at' => now()->toIso8601String(),
        ],
        'items' => [
            [
                'object_type' => 'role',
                'identifier' => ['key' => 'Role1'],
                'operation' => 'create',
                'payload' => ['name' => 'Role1', 'description' => 'Role 1'],
                'meta' => [],
            ],
            [
                'object_type' => 'role',
                'identifier' => ['key' => 'Role2'],
                'operation' => 'create',
                'payload' => ['name' => 'Role2', 'description' => 'Role 2'],
                'meta' => [],
            ],
        ],
    ];

    $filePath = storage_path('app/transports/test-import.json');
    $directory = dirname($filePath);
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    file_put_contents($filePath, json_encode($jsonData, JSON_PRETTY_PRINT));

    $this->artisan('transports:import', ['path' => $filePath, '--force' => true])
        ->assertSuccessful();

    expect(Role::where('name', 'Role1')->exists())->toBeTrue();
    expect(Role::where('name', 'Role2')->exists())->toBeTrue();
});

