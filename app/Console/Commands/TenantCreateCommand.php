<?php

namespace App\Console\Commands;

use App\Services\TenantService;
use Illuminate\Console\Command;

class TenantCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create 
                            {name : Tenant name}
                            {--domain= : Custom domain}
                            {--subdomain= : Subdomain}
                            {--plan=basic : Plan type}
                            {--max-users=10 : Maximum users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant';

    /**
     * Execute the console command.
     */
    public function handle(TenantService $tenantService): int
    {
        $name = $this->argument('name');
        $domain = $this->option('domain');
        $subdomain = $this->option('subdomain') ?? str()->slug($name);
        $plan = $this->option('plan');
        $maxUsers = (int) $this->option('max-users');

        if (! $domain && ! $subdomain) {
            $this->error('Either domain or subdomain must be provided');

            return Command::FAILURE;
        }

        $data = [
            'name' => $name,
            'domain' => $domain,
            'subdomain' => $subdomain,
            'database_prefix' => 'tenant_'.time(),
            'status' => 'active',
            'plan' => $plan,
            'max_users' => $maxUsers,
        ];

        $this->info("Creating tenant: {$name}...");

        $tenant = $tenantService->createTenant($data);

        $this->info('Tenant created successfully!');
        $this->line("ID: {$tenant->id}");
        $this->line('Domain: '.($tenant->domain ?? 'N/A'));
        $this->line('Subdomain: '.($tenant->subdomain ?? 'N/A'));

        return Command::SUCCESS;
    }
}
