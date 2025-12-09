<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Console\Command;

class TenantMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate 
                            {tenant? : Tenant ID or "all" for all tenants}
                            {--fresh : Drop all tables and re-run migrations}
                            {--seed : Seed the database after migrating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations for tenant(s)';

    /**
     * Execute the console command.
     */
    public function handle(TenantService $tenantService): int
    {
        $tenantId = $this->argument('tenant');

        if ($tenantId === 'all') {
            $tenants = Tenant::active()->get();
        } elseif ($tenantId) {
            $tenants = collect([Tenant::findOrFail($tenantId)]);
        } else {
            $this->error('Please provide tenant ID or "all"');

            return Command::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $this->info("Migrating tenant: {$tenant->name} (ID: {$tenant->id})...");

            $tenantService->setCurrentTenant($tenant);

            // Run migrations for each connection
            $connections = [
                'authentication' => 'Modules/Authentication/database/migrations',
                'authorization' => [
                    'Modules/Authorization/database/migrations',
                    'Modules/AuthorizationDebug/database/migrations',
                ],
                'todo' => 'Modules/Todo/database/migrations',
                'config_transports' => 'Modules/ConfigTransports/database/migrations',
                'company' => 'Modules/Company/database/migrations',
                'clipboard' => 'Modules/Clipboard/database/migrations',
            ];

            // Run root migrations first (users and cache tables)
            if (!$this->option('fresh')) {
                \Artisan::call('migrate', [
                    '--database' => 'authentication',
                    '--path' => 'database/migrations/0001_01_01_000000_create_users_table.php',
                ]);

                \Artisan::call('migrate', [
                    '--database' => 'authentication',
                    '--path' => 'database/migrations/0001_01_01_000001_create_cache_table.php',
                ]);
            }

            foreach ($connections as $connection => $paths) {
                $paths = is_array($paths) ? $paths : [$paths];

                foreach ($paths as $path) {
                    $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';
                    $params = [
                        '--database' => $connection,
                        '--path' => $path,
                    ];

                    if ($this->option('seed') && $connection === 'authentication') {
                        $params['--seed'] = true;
                    }

                    \Artisan::call($command, $params);
                }
            }

            $this->info("✓ Migrated: {$tenant->name}");
        }

        return Command::SUCCESS;
    }
}
