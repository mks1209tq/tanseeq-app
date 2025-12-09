<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantService;
use Modules\Authorization\Entities\Role;
use Modules\Authentication\Entities\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTenantUser extends Command
{
    protected $signature = 'tenant:create-user 
                            {tenant : Tenant ID}
                            {email : User email}
                            {--name= : User name}
                            {--password= : User password (default: password123)}
                            {--role=SuperAdmin : Role to assign}';

    protected $description = 'Create a user for a specific tenant';

    public function handle(TenantService $tenantService): int
    {
        $tenantId = $this->argument('tenant');
        $email = $this->argument('email');
        $name = $this->option('name') ?? 'Test User';
        $password = $this->option('password') ?? 'password123';
        $roleName = $this->option('role');

        $tenant = Tenant::findOrFail($tenantId);

        $this->info("Creating user for tenant: {$tenant->name} (ID: {$tenant->id})...");

        // Set current tenant to configure database connections
        $tenantService->setCurrentTenant($tenant);

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->warn("User {$email} already exists in tenant {$tenant->name}.");
            $user = $existingUser;
        } else {
            // Create user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            $this->info("Created user: {$user->email} (ID: {$user->id})");
        }

        // Assign role
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
            $this->info("Assigned role: {$roleName}");
        } else {
            $this->error("Role '{$roleName}' not found!");
            return Command::FAILURE;
        }

        $this->info('');
        $this->info('✓ User created successfully!');
        $this->info('');
        $this->info('Login credentials:');
        $this->info("  Email: {$email}");
        $this->info("  Password: {$password}");
        $this->info("  Tenant: {$tenant->name} (ID: {$tenant->id})");
        $this->info("  Subdomain: {$tenant->subdomain}");

        return Command::SUCCESS;
    }
}

