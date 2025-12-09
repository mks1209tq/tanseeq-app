<?php

namespace App\Console\Commands;

use App\Services\TenantService;
use Illuminate\Console\Command;

class TestTenantResolution extends Command
{
    protected $signature = 'tenant:test-resolution {host : Hostname to test}';

    protected $description = 'Test tenant resolution for a given hostname';

    public function handle(TenantService $tenantService): int
    {
        $host = $this->argument('host');
        
        $this->info("Testing tenant resolution for: {$host}");
        $this->info('');

        // Simulate the hostname
        $originalHost = request()->getHost();
        request()->headers->set('HOST', $host);

        try {
            $tenant = $tenantService->getCurrentTenant();
            
            if ($tenant) {
                $this->info("✓ Tenant resolved successfully!");
                $this->line("  ID: {$tenant->id}");
                $this->line("  Name: {$tenant->name}");
                $this->line("  Subdomain: {$tenant->subdomain}");
                $this->line("  Domain: " . ($tenant->domain ?? 'N/A'));
            } else {
                $this->warn("✗ No tenant found for host: {$host}");
                $this->info('');
                $this->info('Resolution breakdown:');
                
                // Show how it would be parsed
                $parts = explode('.', $host);
                $this->line("  Host parts: " . implode(', ', $parts));
                $this->line("  Part count: " . count($parts));
                
                if (count($parts) >= 3) {
                    $subdomain = $parts[0];
                    $this->line("  Extracted subdomain: {$subdomain}");
                    $this->info('');
                    $this->info("Looking for tenant with subdomain: {$subdomain}");
                } else {
                    $this->warn("  Hostname doesn't have enough parts (need >= 3)");
                }
            }
        } finally {
            // Restore original host
            request()->headers->set('HOST', $originalHost);
        }

        return Command::SUCCESS;
    }
}

