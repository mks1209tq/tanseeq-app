<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ConfigTransports\Entities\TransportImportLog;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can create an import log', function () {
    $log = TransportImportLog::create([
        'transport_number' => 'DEVK900001',
        'import_environment' => 'qa',
        'imported_by' => $this->user->id,
        'status' => 'success',
        'summary' => [
            'total' => 5,
            'success' => 5,
            'failed' => 0,
            'skipped' => 0,
        ],
    ]);

    expect($log->transport_number)->toBe('DEVK900001');
    expect($log->import_environment)->toBe('qa');
    expect($log->status)->toBe('success');
});

it('has relationship with importer', function () {
    $log = TransportImportLog::create([
        'transport_number' => 'DEVK900001',
        'import_environment' => 'qa',
        'imported_by' => $this->user->id,
        'status' => 'success',
        'summary' => [],
    ]);

    expect($log->importer->id)->toBe($this->user->id);
});

it('casts summary to array', function () {
    $summary = [
        'total' => 5,
        'success' => 4,
        'failed' => 1,
        'skipped' => 0,
        'errors' => [
            ['object_type' => 'role', 'identifier' => ['key' => 'TestRole'], 'error' => 'Handler not found'],
        ],
    ];

    $log = TransportImportLog::create([
        'transport_number' => 'DEVK900001',
        'import_environment' => 'qa',
        'imported_by' => $this->user->id,
        'status' => 'partial',
        'summary' => $summary,
    ]);

    expect($log->summary)->toBeArray();
    expect($log->summary)->toHaveKey('total');
    expect($log->summary)->toHaveKey('success');
    expect($log->summary)->toHaveKey('failed');
    expect($log->summary)->toHaveKey('errors');
});

it('can handle different status types', function () {
    $statuses = ['success', 'partial', 'failed'];

    foreach ($statuses as $status) {
        $log = TransportImportLog::create([
            'transport_number' => 'DEVK900001',
            'import_environment' => 'qa',
            'imported_by' => $this->user->id,
            'status' => $status,
            'summary' => [],
        ]);

        expect($log->status)->toBe($status);
    }
});

it('can track import environment', function () {
    $environments = ['qa', 'prod'];

    foreach ($environments as $environment) {
        $log = TransportImportLog::create([
            'transport_number' => 'DEVK900001',
            'import_environment' => $environment,
            'imported_by' => $this->user->id,
            'status' => 'success',
            'summary' => [],
        ]);

        expect($log->import_environment)->toBe($environment);
    }
});

