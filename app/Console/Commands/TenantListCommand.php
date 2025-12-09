<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all tenants';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found.');

            return Command::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Domain', 'Subdomain', 'Status', 'Plan', 'Created'],
            $tenants->map(function ($tenant) {
                return [
                    $tenant->id,
                    $tenant->name,
                    $tenant->domain ?? 'N/A',
                    $tenant->subdomain ?? 'N/A',
                    $tenant->status,
                    $tenant->plan,
                    $tenant->created_at->format('Y-m-d H:i'),
                ];
            })
        );

        return Command::SUCCESS;
    }
}
