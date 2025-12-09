<?php

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a tenant', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'Test Tenant',
        'subdomain' => 'test',
    ]);

    expect($tenant->name)->toBe('Test Tenant');
    expect($tenant->subdomain)->toBe('test');
    expect($tenant->status)->toBe('active');
});

it('can check if tenant is active', function () {
    $activeTenant = Tenant::factory()->active()->create();
    $suspendedTenant = Tenant::factory()->suspended()->create();
    $expiredTenant = Tenant::factory()->expired()->create();

    expect($activeTenant->isActive())->toBeTrue();
    expect($suspendedTenant->isActive())->toBeFalse();
    expect($expiredTenant->isActive())->toBeFalse();
});

it('can get database path for tenant', function () {
    $tenant = Tenant::factory()->create();

    $path = $tenant->getDatabasePath('authentication');

    expect($path)->toContain("tenants/{$tenant->id}/authentication.sqlite");
});

it('can resolve tenant from subdomain', function () {
    $tenant = Tenant::factory()->create([
        'subdomain' => 'test-tenant',
    ]);

    // Mock request host
    request()->headers->set('Host', 'test-tenant.example.com');

    $tenantService = app(TenantService::class);
    $resolved = $tenantService->getCurrentTenant();

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($tenant->id);
});

it('can resolve tenant from header', function () {
    $tenant = Tenant::factory()->create();

    $request = \Illuminate\Http\Request::create('/');
    $request->headers->set('X-Tenant-ID', (string) $tenant->id);
    app()->instance('request', $request);

    $tenantService = new TenantService();
    $resolved = $tenantService->getCurrentTenant();

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($tenant->id);
});

it('configures tenant database connections', function () {
    $tenant = Tenant::factory()->create();

    $tenantService = app(TenantService::class);
    $tenantService->setCurrentTenant($tenant);

    $authDb = config('database.connections.authentication.database');
    expect($authDb)->toContain("tenants/{$tenant->id}/authentication.sqlite");
});

it('initializes tenant databases on creation', function () {
    $tenantService = app(TenantService::class);

    $tenant = $tenantService->createTenant([
        'name' => 'New Tenant',
        'subdomain' => 'new-tenant',
        'database_prefix' => 'tenant_new',
        'status' => 'active',
        'plan' => 'basic',
        'max_users' => 10,
    ]);

    $authPath = $tenant->getDatabasePath('authentication');
    expect(file_exists($authPath))->toBeTrue();
});

