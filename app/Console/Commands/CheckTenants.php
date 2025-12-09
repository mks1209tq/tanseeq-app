<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class CheckTenants extends Command
{
    protected $signature = 'tenant:check';

    protected $description = 'Check tenants in system database';

    public function handle(): int
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found.');
            return Command::SUCCESS;
        }

        $this->info('Tenants in system database:');
        foreach ($tenants as $tenant) {
            $this->line("  ID: {$tenant->id} - {$tenant->name} (subdomain: {$tenant->subdomain}, status: {$tenant->status})");
        }

        $tenant1 = Tenant::find(1);
        if ($tenant1) {
            $this->info('');
            $this->info('✓ Tenant ID 1 exists');
            $this->info("  Database path: {$tenant1->getDatabasePath('authentication')}");
        } else {
            $this->warn('');
            $this->warn('✗ Tenant ID 1 does NOT exist');
            $firstTenant = $tenants->first();
            if ($firstTenant) {
                $this->info("  First tenant is ID: {$firstTenant->id}");
            }
        }

        return Command::SUCCESS;
    }
}

