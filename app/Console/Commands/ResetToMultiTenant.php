<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Entities\User;
use Modules\Authorization\Entities\Role;

class ResetToMultiTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:reset 
                            {--force : Force reset without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all databases and set up fresh multi-tenant environment';

    /**
     * Execute the console command.
     */
    public function handle(TenantService $tenantService): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('This will delete ALL databases and data. Are you sure?', false)) {
                $this->info('Reset cancelled.');

                return Command::SUCCESS;
            }
        }

        $this->info('Resetting to multi-tenant architecture...');

        // Step 1: Delete all tenant databases
        $this->info('Deleting tenant databases...');
        $tenantDir = base_path('tenants');
        if (File::exists($tenantDir)) {
            File::deleteDirectory($tenantDir);
        }
        // Ensure tenants directory exists
        if (! File::exists($tenantDir)) {
            File::makeDirectory($tenantDir, 0755, true);
        }

        // Step 2: Delete old module-level databases
        $this->info('Deleting old module-level databases...');
        $moduleDatabases = [
            'Modules/Authentication/database/authentication.sqlite',
            'Modules/Authorization/database/authorization.sqlite',
            'Modules/Todo/database/todo.sqlite',
            'Modules/ConfigTransports/database/config_transports.sqlite',
            'Modules/Company/database/company.sqlite',
            'Modules/Clipboard/database/clipboard.sqlite',
        ];

        foreach ($moduleDatabases as $db) {
            $path = base_path($db);
            if (File::exists($path)) {
                File::delete($path);
                $this->line("  Deleted: {$db}");
            }
        }

        // Step 3: Clear system database (keep structure, remove data)
        $this->info('Clearing system database...');
        try {
            DB::connection('system')->statement('PRAGMA foreign_keys=OFF;');
            
            // Delete all tenants first
            Tenant::query()->delete();
            
            // Reset auto-increment for tenants table to start at 1
            DB::connection('system')->statement('DELETE FROM sqlite_sequence WHERE name = "tenants"');
            DB::connection('system')->statement('INSERT INTO sqlite_sequence (name, seq) VALUES ("tenants", 0)');

            $tables = DB::connection('system')->select("SELECT name FROM sqlite_master WHERE type='table' AND name != 'sqlite_sequence'");
            foreach ($tables as $table) {
                if ($table->name !== 'tenants') {
                    DB::connection('system')->statement('DELETE FROM '.$table->name);
                }
            }
            DB::connection('system')->statement('PRAGMA foreign_keys=ON;');
        } catch (\Exception $e) {
            $this->warn("Could not clear system database: {$e->getMessage()}");
        }

        // Step 4: Delete any existing tenant directories
        $this->info('Cleaning up tenant directories...');
        if (File::exists($tenantDir)) {
            $tenantDirs = File::directories($tenantDir);
            foreach ($tenantDirs as $dir) {
                File::deleteDirectory($dir);
            }
        }

        // Step 5: Create Tenant 1
        $this->info('Creating Tenant 1...');
        $tenant = Tenant::create([
            'name' => 'Tenant 1',
            'subdomain' => 'default',
            'domain' => null,
            'database_prefix' => 'tenant_1',
            'status' => 'active',
            'plan' => 'enterprise',
            'max_users' => 1000,
        ]);

        $this->info("Created tenant: {$tenant->name} (ID: {$tenant->id})");

        // Step 6: Initialize tenant databases and run migrations
        $this->info('Initializing tenant databases...');
        $tenantService->initializeTenantDatabases($tenant);

        // Step 7: Run authorization seeder (after migrations)
        $this->info('Seeding authorization data...');
        try {
            \Artisan::call('db:seed', [
                '--class' => 'Modules\\Authorization\\Database\\Seeders\\AuthorizationDatabaseSeeder',
                '--database' => 'authorization',
            ]);
        } catch (\Exception $e) {
            $this->error("Seeder error: {$e->getMessage()}");
            // Continue anyway - seeder might have partially run
        }

        // Step 8: Create user a@a.com
        $this->info('Creating user a@a.com...');
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'a@a.com',
            'password' => Hash::make('asdf1234'),
            'email_verified_at' => now(),
        ]);

        $this->info("Created user: {$user->email} (ID: {$user->id})");

        // Step 9: Assign SuperAdmin role
        $this->info('Assigning SuperAdmin role...');
        $superAdminRole = Role::where('name', 'SuperAdmin')->first();
        if ($superAdminRole) {
            $user->roles()->sync([$superAdminRole->id]);
            $this->info('SuperAdmin role assigned.');
        } else {
            $this->error('SuperAdmin role not found!');
        }

        // Step 10: Clear all caches
        $this->info('Clearing caches...');
        try {
            \Artisan::call('cache:clear');
        } catch (\Exception $e) {
            // Cache table might not exist yet, that's okay
        }
        \Artisan::call('config:clear');
        \Artisan::call('route:clear');
        \Artisan::call('view:clear');

        $this->info('');
        $this->info('✓ Reset complete!');
        $this->info('');
        $this->info('Login credentials:');
        $this->info('  Email: a@a.com');
        $this->info('  Password: asdf1234');
        $this->info('');
        $this->info('Tenant ID: 1');
        $this->info('Database location: tenants/1/');

        return Command::SUCCESS;
    }
}
