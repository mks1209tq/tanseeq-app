<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Console\Command;

class TenantSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:seed 
                            {tenant : Tenant ID}
                            {--class= : Specific seeder class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed database for a tenant';

    /**
     * Execute the console command.
     */
    public function handle(TenantService $tenantService): int
    {
        $tenant = Tenant::findOrFail($this->argument('tenant'));

        $this->info("Seeding tenant: {$tenant->name}...");

        $tenantService->setCurrentTenant($tenant);

        $params = [];
        if ($this->option('class')) {
            $params['--class'] = $this->option('class');
        }

        \Artisan::call('db:seed', $params);

        $this->info("✓ Seeded: {$tenant->name}");

        return Command::SUCCESS;
    }
}
