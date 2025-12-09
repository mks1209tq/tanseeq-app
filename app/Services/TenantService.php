<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class TenantService
{
    protected ?Tenant $currentTenant = null;

    /**
     * Get the current tenant.
     */
    public function getCurrentTenant(): ?Tenant
    {
        if ($this->currentTenant) {
            return $this->currentTenant;
        }

        // Check if system connection is configured
        if (! config('database.connections.system')) {
            return null;
        }

        // Try multiple resolution strategies
        try {
            $tenant = $this->resolveFromDomain()
                ?? $this->resolveFromSubdomain()
                ?? $this->resolveFromHeader()
                ?? $this->resolveFromSession()
                ?? $this->resolveFromDefault();

            if ($tenant && $tenant->isActive()) {
                $this->currentTenant = $tenant;

                return $tenant;
            }
        } catch (\Exception $e) {
            // Log error but don't break the application
            \Log::warning('Error resolving tenant: '.$e->getMessage());

            return null;
        }

        return null;
    }

    /**
     * Set the current tenant.
     */
    public function setCurrentTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
        app()->instance('tenant', $tenant);
        $this->configureTenantConnections($tenant);
    }

    /**
     * Configure database connections for a tenant.
     */
    public function configureTenantConnections(Tenant $tenant): void
    {
        $connections = [
            'authentication',
            'authorization',
            'todo',
            'config_transports',
            'company',
            'clipboard',
        ];

        foreach ($connections as $connection) {
            $databasePath = $tenant->getDatabasePath($connection);

            // Ensure directory exists
            $directory = dirname($databasePath);
            if (! File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Create database file if it doesn't exist
            if (! File::exists($databasePath)) {
                File::put($databasePath, '');
            }

            Config::set("database.connections.{$connection}.database", $databasePath);
        }

        // Refresh database connections
        foreach ($connections as $connection) {
            \Illuminate\Support\Facades\DB::purge($connection);
        }
    }

    /**
     * Resolve tenant from domain.
     */
    protected function resolveFromDomain(): ?Tenant
    {
        $domain = request()->getHost();

        return Tenant::where('domain', $domain)
            ->active()
            ->first();
    }

    /**
     * Resolve tenant from subdomain.
     */
    protected function resolveFromSubdomain(): ?Tenant
    {
        $host = request()->getHost();
        $parts = explode('.', $host);

        if (count($parts) >= 3) {
            $subdomain = $parts[0];

            return Tenant::where('subdomain', $subdomain)
                ->active()
                ->first();
        }

        return null;
    }

    /**
     * Resolve tenant from X-Tenant-ID header.
     */
    protected function resolveFromHeader(): ?Tenant
    {
        $tenantId = request()->header('X-Tenant-ID');

        if ($tenantId) {
            return Tenant::where('id', $tenantId)
                ->active()
                ->first();
        }

        return null;
    }

    /**
     * Resolve tenant from session.
     */
    protected function resolveFromSession(): ?Tenant
    {
        $tenantId = session('tenant_id');

        if ($tenantId) {
            return Tenant::where('id', $tenantId)
                ->active()
                ->first();
        }

        return null;
    }

    /**
     * Resolve tenant from default (for development).
     */
    protected function resolveFromDefault(): ?Tenant
    {
        $defaultTenantId = config('tenancy.default_tenant_id');

        if ($defaultTenantId) {
            return Tenant::where('id', $defaultTenantId)
                ->active()
                ->first();
        }

        return null;
    }

    /**
     * Create a new tenant and initialize databases.
     */
    public function createTenant(array $data): Tenant
    {
        // Ensure database_prefix is set
        if (! isset($data['database_prefix'])) {
            $data['database_prefix'] = 'tenant_'.time();
        }

        $tenant = Tenant::create($data);
        $this->initializeTenantDatabases($tenant);

        return $tenant;
    }

    /**
     * Initialize databases for a new tenant.
     */
    public function initializeTenantDatabases(Tenant $tenant): void
    {
        $connections = [
            'authentication',
            'authorization',
            'todo',
            'config_transports',
            'company',
            'clipboard',
        ];

        foreach ($connections as $connection) {
            $databasePath = $tenant->getDatabasePath($connection);
            $directory = dirname($databasePath);

            if (! File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            File::put($databasePath, '');
        }

        // Run migrations for tenant
        $this->runTenantMigrations($tenant);
    }

    /**
     * Run migrations for a tenant.
     */
    public function runTenantMigrations(Tenant $tenant): void
    {
        $this->setCurrentTenant($tenant);

        // Run root migrations first (users table)
        \Artisan::call('migrate', [
            '--database' => 'authentication',
            '--path' => 'database/migrations/0001_01_01_000000_create_users_table.php',
            '--force' => true,
        ]);

        // Run cache migration for authentication connection
        \Artisan::call('migrate', [
            '--database' => 'authentication',
            '--path' => 'database/migrations/0001_01_01_000001_create_cache_table.php',
            '--force' => true,
        ]);

        // Run module migrations
        \Artisan::call('migrate', [
            '--database' => 'authentication',
            '--path' => 'Modules/Authentication/database/migrations',
            '--force' => true,
        ]);

        \Artisan::call('migrate', [
            '--database' => 'authorization',
            '--path' => 'Modules/Authorization/database/migrations',
            '--force' => true,
        ]);

        \Artisan::call('migrate', [
            '--database' => 'authorization',
            '--path' => 'Modules/AuthorizationDebug/database/migrations',
            '--force' => true,
        ]);

        \Artisan::call('migrate', [
            '--database' => 'todo',
            '--path' => 'Modules/Todo/database/migrations',
            '--force' => true,
        ]);

        \Artisan::call('migrate', [
            '--database' => 'config_transports',
            '--path' => 'Modules/ConfigTransports/database/migrations',
            '--force' => true,
        ]);

        \Artisan::call('migrate', [
            '--database' => 'company',
            '--path' => 'Modules/Company/database/migrations',
            '--force' => true,
        ]);

        \Artisan::call('migrate', [
            '--database' => 'clipboard',
            '--path' => 'Modules/Clipboard/database/migrations',
            '--force' => true,
        ]);
    }

    /**
     * Delete tenant and all its data.
     */
    public function deleteTenant(Tenant $tenant): void
    {
        $tenantDirectory = base_path("tenants/{$tenant->id}");

        if (File::exists($tenantDirectory)) {
            File::deleteDirectory($tenantDirectory);
        }

        $tenant->delete();
    }
}
