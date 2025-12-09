<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantService;
use Modules\Authentication\Entities\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyTenantIsolation extends Command
{
    protected $signature = 'tenant:verify-isolation';

    protected $description = 'Verify tenant database isolation';

    public function handle(TenantService $tenantService): int
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');
            return Command::FAILURE;
        }

        $this->info('Verifying tenant database isolation...');
        $this->info('');

        foreach ($tenants as $tenant) {
            $this->info("=== Tenant {$tenant->id}: {$tenant->name} ===");
            $this->line("Subdomain: {$tenant->subdomain}");
            
            // Set current tenant
            $tenantService->setCurrentTenant($tenant);

            // Get users from this tenant's database
            $users = User::all();
            $this->line("Users in database: {$users->count()}");
            
            foreach ($users as $user) {
                $roles = $user->roles->pluck('name')->join(', ');
                $this->line("  - {$user->email} ({$user->name}) [Roles: {$roles}]");
            }

            // Get database path
            $dbPath = $tenant->getDatabasePath('authentication');
            $this->line("Database: {$dbPath}");
            $this->line("Database exists: " . (file_exists($dbPath) ? 'Yes' : 'No'));
            $this->info('');
        }

        $this->info('✓ Isolation check complete!');
        $this->info('');
        $this->info('To test multi-tenancy:');
        $this->info('1. Access via subdomain: http://tenant2.test (for Tenant 2)');
        $this->info('2. Or use X-Tenant-ID header in requests');
        $this->info('3. Each tenant should only see their own data');

        return Command::SUCCESS;
    }
}

