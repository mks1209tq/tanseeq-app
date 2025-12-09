<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class MigrateUserToTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:migrate-to-tenant 
                            {email : Email of the user to migrate}
                            {--tenant-id=1 : Tenant ID to migrate user to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate a user from module database to tenant database';

    /**
     * Execute the console command.
     */
    public function handle(TenantService $tenantService): int
    {
        $email = $this->argument('email');
        $tenantId = $this->option('tenant-id');

        // Get tenant
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");

            return Command::FAILURE;
        }

        $this->info("Migrating user {$email} to Tenant {$tenantId} ({$tenant->name})...");

        // Configure module database connection (source)
        $moduleDbPath = base_path('Modules/Authentication/database/authentication.sqlite');
        if (! file_exists($moduleDbPath)) {
            $this->error("Module database not found at: {$moduleDbPath}");

            return Command::FAILURE;
        }

        Config::set('database.connections.module_auth', [
            'driver' => 'sqlite',
            'database' => $moduleDbPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Find user in module database first
        $user = DB::connection('module_auth')->table('users')->where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found in module database.");

            return Command::FAILURE;
        }

        $this->info("Found user in module database: {$user->name} (ID: {$user->id})");

        // Set tenant and configure tenant database connection (destination)
        $tenantService->setCurrentTenant($tenant);
        $tenantDbPath = $tenant->getDatabasePath('authentication');

        // Ensure tenant database exists
        if (! file_exists($tenantDbPath) || filesize($tenantDbPath) === 0) {
            $this->info('Tenant database not found or empty. Creating tenant database...');
            $tenantService->initializeTenantDatabases($tenant);
        } else {
            // Ensure migrations are run - need to run root migrations first, then module migrations
            $this->info('Running migrations for tenant...');

            // Run root migrations (users table) - only the users migration
            \Artisan::call('migrate', [
                '--database' => 'authentication',
                '--path' => 'database/migrations/0001_01_01_000000_create_users_table.php',
                '--force' => true,
            ]);

            // Run module migrations (auth_settings)
            \Artisan::call('migrate', [
                '--database' => 'authentication',
                '--path' => 'Modules/Authentication/database/migrations',
                '--force' => true,
            ]);
        }

        // Refresh connection after migrations
        DB::purge('authentication');
        DB::reconnect('authentication');

        // Verify tables exist
        try {
            $tableExists = DB::connection('authentication')->select("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            if (empty($tableExists)) {
                $this->error('Users table does not exist in tenant database after migrations.');

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Failed to verify tenant database: {$e->getMessage()}");

            return Command::FAILURE;
        }

        // Check if user already exists in tenant database
        $existingUser = DB::connection('authentication')->table('users')->where('email', $email)->first();

        if ($existingUser) {
            if ($this->confirm("User {$email} already exists in tenant database. Update existing user?", true)) {
                // Update existing user
                DB::connection('authentication')->table('users')
                    ->where('id', $existingUser->id)
                    ->update([
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $user->password,
                        'email_verified_at' => $user->email_verified_at,
                        'remember_token' => $user->remember_token,
                        'updated_at' => now(),
                    ]);

                $userId = $existingUser->id;
                $this->info("Updated existing user (ID: {$userId})");
            } else {
                $this->info('Migration cancelled.');

                return Command::SUCCESS;
            }
        } else {
            // Insert new user
            $userId = DB::connection('authentication')->table('users')->insertGetId([
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
                'email_verified_at' => $user->email_verified_at,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);

            $this->info("Created user in tenant database (ID: {$userId})");
        }

        // Migrate sessions
        $sessions = DB::connection('module_auth')
            ->table('sessions')
            ->where('user_id', $user->id)
            ->get();

        if ($sessions->count() > 0) {
            foreach ($sessions as $session) {
                DB::connection('authentication')->table('sessions')->updateOrInsert(
                    ['id' => $session->id],
                    [
                        'user_id' => $userId,
                        'ip_address' => $session->ip_address,
                        'user_agent' => $session->user_agent,
                        'payload' => $session->payload,
                        'last_activity' => $session->last_activity,
                    ]
                );
            }
            $this->info("Migrated {$sessions->count()} session(s)");
        }

        // Migrate password reset tokens
        $passwordReset = DB::connection('module_auth')
            ->table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if ($passwordReset) {
            DB::connection('authentication')->table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => $passwordReset->token,
                    'created_at' => $passwordReset->created_at,
                ]
            );
            $this->info('Migrated password reset token');
        }

        $this->info("✓ Successfully migrated user {$email} to Tenant {$tenantId}");
        $this->info("  User ID in tenant database: {$userId}");
        $this->info("  Database: {$tenantDbPath}");

        return Command::SUCCESS;
    }
}
