<?php

namespace App\Console\Commands;

use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateToMultiTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate-existing 
                            {--tenant-name=Default : Name for the default tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing single-tenant data to multi-tenant structure';

    /**
     * Execute the console command.
     */
    public function handle(TenantService $tenantService): int
    {
        $this->info('Migrating existing data to multi-tenant structure...');

        // Create default tenant
        $tenant = $tenantService->createTenant([
            'name' => $this->option('tenant-name'),
            'subdomain' => 'default',
            'database_prefix' => 'tenant_1',
            'status' => 'active',
            'plan' => 'enterprise',
        ]);

        // Copy existing databases to tenant directory
        $connections = [
            'authentication' => 'Modules/Authentication/database/authentication.sqlite',
            'authorization' => 'Modules/Authorization/database/authorization.sqlite',
            'todo' => 'Modules/Todo/database/todo.sqlite',
            'config_transports' => 'Modules/ConfigTransports/database/config_transports.sqlite',
            'company' => 'Modules/Company/database/company.sqlite',
            'clipboard' => 'Modules/Clipboard/database/clipboard.sqlite',
        ];

        foreach ($connections as $connection => $sourcePath) {
            $source = base_path($sourcePath);
            $destination = $tenant->getDatabasePath($connection);

            if (File::exists($source)) {
                File::copy($source, $destination);
                $this->info("Copied {$connection} database");
            }
        }

        $this->info("Migration complete! Default tenant ID: {$tenant->id}");

        return Command::SUCCESS;
    }
}
