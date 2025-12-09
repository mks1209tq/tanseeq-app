<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ModuleSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:setup {name : The name of the module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module and configure it for the project standards';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $moduleName = $this->argument('name');
        $moduleNameLower = Str::lower($moduleName);
        $moduleNameStudly = Str::studly($moduleName);
        $moduleNameUpper = Str::upper($moduleName);

        $this->info("Setting up module: {$moduleNameStudly}");

        // Step 1: Create the module
        $this->info('Creating module structure...');
        $this->call('module:make', ['name' => $moduleName]);

        // Step 2: Update composer.json
        $this->info('Updating composer.json...');
        $this->updateComposerJson($moduleNameStudly, $moduleNameLower);

        // Step 3: Update database.php
        $this->info('Updating database configuration...');
        $this->updateDatabaseConfig($moduleNameLower);

        // Step 4: Update TenantService
        $this->info('Updating TenantService...');
        $this->updateTenantService($moduleNameLower);

        // Step 5: Update TenantMigrateCommand
        $this->info('Updating TenantMigrateCommand...');
        $this->updateTenantMigrateCommand($moduleNameLower);

        // Step 6: Update MigrateToMultiTenant
        $this->info('Updating MigrateToMultiTenant command...');
        $this->updateMigrateToMultiTenant($moduleNameLower);

        // Step 7: Update AuthorizationDatabaseSeeder
        $this->info('Updating AuthorizationDatabaseSeeder...');
        $this->updateAuthorizationSeeder($moduleNameStudly, $moduleNameUpper);

        // Step 8: Run composer dump-autoload
        $this->info('Running composer dump-autoload...');
        $this->call('composer', ['dump-autoload']);

        $this->info("✓ Module {$moduleNameStudly} has been set up successfully!");
        $this->newLine();
        $this->info('Next steps:');
        $this->line("1. Create your migrations in Modules/{$moduleNameStudly}/database/migrations");
        $this->line("2. Create your models in Modules/{$moduleNameStudly}/app/Entities");
        $this->line('3. Run migrations: php artisan tenant:migrate 1');

        return Command::SUCCESS;
    }

    /**
     * Update composer.json with module autoload entries.
     */
    protected function updateComposerJson(string $moduleNameStudly, string $moduleNameLower): void
    {
        $composerPath = base_path('composer.json');
        $composer = json_decode(file_get_contents($composerPath), true);

        // Add to autoload
        $autoloadEntries = [
            "Modules\\{$moduleNameStudly}\\" => "Modules/{$moduleNameStudly}/app/",
            "Modules\\{$moduleNameStudly}\\Entities\\" => "Modules/{$moduleNameStudly}/app/Entities/",
            "Modules\\{$moduleNameStudly}\\Database\\Factories\\" => "Modules/{$moduleNameStudly}/database/factories/",
            "Modules\\{$moduleNameStudly}\\Database\\Seeders\\" => "Modules/{$moduleNameStudly}/database/seeders/",
        ];

        foreach ($autoloadEntries as $namespace => $path) {
            if (! isset($composer['autoload']['psr-4'][$namespace])) {
                $composer['autoload']['psr-4'][$namespace] = $path;
            }
        }

        // Add to autoload-dev
        $testNamespace = "Modules\\{$moduleNameStudly}\\Tests\\";
        $testPath = "Modules/{$moduleNameStudly}/tests/";
        if (! isset($composer['autoload-dev']['psr-4'][$testNamespace])) {
            $composer['autoload-dev']['psr-4'][$testNamespace] = $testPath;
        }

        // Sort autoload entries
        ksort($composer['autoload']['psr-4']);
        ksort($composer['autoload-dev']['psr-4']);

        file_put_contents($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * Update database.php with module connection.
     */
    protected function updateDatabaseConfig(string $moduleNameLower): void
    {
        $configPath = config_path('database.php');
        $content = file_get_contents($configPath);

        // Check if connection already exists
        if (strpos($content, "'{$moduleNameLower}' => [") !== false) {
            $this->warn("Database connection '{$moduleNameLower}' already exists.");

            return;
        }

        // Find the insertion point (before 'todo' connection)
        $insertBefore = "'todo' => [";
        $moduleNameUpper = strtoupper($moduleNameLower);
        $connectionCode = "        '{$moduleNameLower}' => [\n".
            "            'driver' => env('{$moduleNameUpper}_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),\n".
            "            'url' => env('{$moduleNameUpper}_DB_URL'),\n".
            "            'host' => env('{$moduleNameUpper}_DB_HOST', env('DB_HOST', '127.0.0.1')),\n".
            "            'port' => env('{$moduleNameUpper}_DB_PORT', env('DB_PORT', '3306')),\n".
            "            'database' => env('{$moduleNameUpper}_DB_DATABASE', base_path('tenants/1/{$moduleNameLower}.sqlite')),\n".
            "            'username' => env('{$moduleNameUpper}_DB_USERNAME', env('DB_USERNAME', 'root')),\n".
            "            'password' => env('{$moduleNameUpper}_DB_PASSWORD', env('DB_PASSWORD', '')),\n".
            "            'unix_socket' => env('{$moduleNameUpper}_DB_SOCKET', env('DB_SOCKET', '')),\n".
            "            'charset' => env('{$moduleNameUpper}_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),\n".
            "            'collation' => env('{$moduleNameUpper}_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),\n".
            "            'prefix' => '',\n".
            "            'prefix_indexes' => true,\n".
            "            'strict' => true,\n".
            "            'engine' => null,\n".
            "            'foreign_key_constraints' => env('{$moduleNameUpper}_DB_FOREIGN_KEYS', true),\n".
            "            'options' => extension_loaded('pdo_mysql') ? array_filter([\n".
            "                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),\n".
            "            ]) : [],\n".
            "        ],\n\n";

        $content = str_replace($insertBefore, $connectionCode.$insertBefore, $content);
        file_put_contents($configPath, $content);
    }

    /**
     * Update TenantService with module connection.
     */
    protected function updateTenantService(string $moduleNameLower): void
    {
        $servicePath = app_path('Services/TenantService.php');
        $content = file_get_contents($servicePath);

        // Check if already added
        if (strpos($content, "'{$moduleNameLower}',") !== false) {
            $this->warn("Module '{$moduleNameLower}' already in TenantService.");

            return;
        }

        // Add to configureTenantConnections
        $connectionsArray = "            'company',\n        ];";
        $content = str_replace(
            $connectionsArray,
            "            'company',\n            '{$moduleNameLower}',\n        ];",
            $content
        );

        // Add to initializeTenantDatabases
        $content = str_replace(
            "'company',\n        ];",
            "'company',\n            '{$moduleNameLower}',\n        ];",
            $content
        );

        // Add to runTenantMigrations
        $moduleNameStudly = Str::studly($moduleNameLower);
        $migrationCode = "\n\n        \\Artisan::call('migrate', [\n".
            "            '--database' => '{$moduleNameLower}',\n".
            "            '--path' => 'Modules/{$moduleNameStudly}/database/migrations',\n".
            '        ]);';

        $insertBefore = '    }';
        $lastMigration = "            '--path' => 'Modules/Company/database/migrations',";
        $content = str_replace(
            $lastMigration."\n        ]);",
            $lastMigration."\n        ]);".$migrationCode,
            $content
        );

        file_put_contents($servicePath, $content);
    }

    /**
     * Update TenantMigrateCommand with module connection.
     */
    protected function updateTenantMigrateCommand(string $moduleNameLower): void
    {
        $commandPath = app_path('Console/Commands/TenantMigrateCommand.php');
        $content = file_get_contents($commandPath);

        // Check if already added
        if (strpos($content, "'{$moduleNameLower}' =>") !== false) {
            $this->warn("Module '{$moduleNameLower}' already in TenantMigrateCommand.");

            return;
        }

        $moduleNameStudly = Str::studly($moduleNameLower);
        $connectionEntry = "                '{$moduleNameLower}' => 'Modules/{$moduleNameStudly}/database/migrations',";

        $lastConnection = "'company' => 'Modules/Company/database/migrations',";
        $content = str_replace(
            $lastConnection."\n            ];",
            $lastConnection.",\n                ".$connectionEntry."\n            ];",
            $content
        );

        file_put_contents($commandPath, $content);
    }

    /**
     * Update MigrateToMultiTenant with module connection.
     */
    protected function updateMigrateToMultiTenant(string $moduleNameLower): void
    {
        $commandPath = app_path('Console/Commands/MigrateToMultiTenant.php');
        $content = file_get_contents($commandPath);

        // Check if already added
        if (strpos($content, "'{$moduleNameLower}' =>") !== false) {
            $this->warn("Module '{$moduleNameLower}' already in MigrateToMultiTenant.");

            return;
        }

        $moduleNameStudly = Str::studly($moduleNameLower);
        $connectionEntry = "            '{$moduleNameLower}' => 'Modules/{$moduleNameStudly}/database/{$moduleNameLower}.sqlite',";

        $lastConnection = "'company' => 'Modules/Company/database/company.sqlite',";
        $content = str_replace(
            $lastConnection."\n        ];",
            $lastConnection.",\n            ".$connectionEntry."\n        ];",
            $content
        );

        file_put_contents($commandPath, $content);
    }

    /**
     * Update AuthorizationDatabaseSeeder with module authorization object.
     */
    protected function updateAuthorizationSeeder(string $moduleNameStudly, string $moduleNameUpper): void
    {
        $seederPath = base_path('Modules/Authorization/database/seeders/AuthorizationDatabaseSeeder.php');
        $content = file_get_contents($seederPath);

        $authObjectCode = strtoupper($moduleNameStudly).'_MANAGEMENT';

        // Check if already added
        if (strpos($content, $authObjectCode) !== false) {
            $this->warn("Authorization object '{$authObjectCode}' already exists.");

            return;
        }

        // Add authorization object creation
        $moduleNameLowerVar = strtolower($moduleNameStudly);
        $objectCode = "\n\n        // Create {$moduleNameStudly} Management object\n".
            "        \${$moduleNameLowerVar}Management = AuthObject::firstOrCreate(\n".
            "            ['code' => '{$authObjectCode}'],\n".
            "            ['description' => '{$moduleNameStudly} Management - Controls access to manage {$moduleNameLowerVar}']\n".
            '        );';

        // Add authorization object after Company Management
        $insertAfter = "        // Create Company Management object\n".
            "        \$companyManagement = AuthObject::firstOrCreate(\n".
            "            ['code' => 'COMPANY_MANAGEMENT'],\n".
            "            ['description' => 'Company Management - Controls access to manage companies']\n".
            '        );';
        $content = str_replace(
            $insertAfter,
            $insertAfter.$objectCode,
            $content
        );

        // Add ACTVT field after companyManagement's ACTVT field
        $moduleNameLowerVar = strtolower($moduleNameStudly);
        $fieldCode = "\n\n        AuthObjectField::firstOrCreate(\n".
            "            [\n".
            "                'auth_object_id' => \${$moduleNameLowerVar}Management->id,\n".
            "                'code' => 'ACTVT',\n".
            "            ],\n".
            "            [\n".
            "                'label' => 'Activity',\n".
            "                'is_org_level' => false,\n".
            "                'sort' => 1,\n".
            "            ]\n".
            '        );';

        $insertAfter = "        AuthObjectField::firstOrCreate(\n".
            "            [\n".
            "                'auth_object_id' => \$companyManagement->id,\n".
            "                'code' => 'ACTVT',\n".
            "            ],\n".
            "            [\n".
            "                'label' => 'Activity',\n".
            "                'is_org_level' => false,\n".
            "                'sort' => 1,\n".
            "            ]\n".
            '        );';
        $content = str_replace(
            $insertAfter,
            $insertAfter.$fieldCode,
            $content
        );

        // Add SuperAdmin authorization after superAdminCompanyAuth
        $moduleNameLowerVar = strtolower($moduleNameStudly);
        $superAdminCode = "\n\n        \${$moduleNameLowerVar}SuperAdminAuth = RoleAuthorization::firstOrCreate(\n".
            "            [\n".
            "                'role_id' => \$superAdminRole->id,\n".
            "                'auth_object_id' => \${$moduleNameLowerVar}Management->id,\n".
            "            ],\n".
            "            [\n".
            "                'label' => 'SuperAdmin - Full Access to {$moduleNameStudly} Management',\n".
            "            ]\n".
            "        );\n\n".
            "        RoleAuthorizationField::firstOrCreate(\n".
            "            [\n".
            "                'role_authorization_id' => \${$moduleNameLowerVar}SuperAdminAuth->id,\n".
            "                'field_code' => 'ACTVT',\n".
            "            ],\n".
            "            [\n".
            "                'operator' => '*',\n".
            "            ]\n".
            '        );';

        $insertAfter = "        RoleAuthorizationField::firstOrCreate(\n".
            "            [\n".
            "                'role_authorization_id' => \$superAdminCompanyAuth->id,\n".
            "                'field_code' => 'ACTVT',\n".
            "            ],\n".
            "            [\n".
            "                'operator' => '*',\n".
            "            ]\n".
            '        );';
        $content = str_replace(
            $insertAfter,
            $insertAfter.$superAdminCode,
            $content
        );

        // Update the info message - find and increment auth objects count
        $content = preg_replace(
            "/(- \d+ Auth Objects)/",
            function ($matches) {
                $count = (int) preg_replace('/[^0-9]/', '', $matches[0]);

                return '- '.($count + 1).' Auth Objects';
            },
            $content
        );

        // Add to the list in the info message
        $infoList = 'COMPANY_MANAGEMENT)';
        if (strpos($content, $infoList) !== false) {
            $content = str_replace(
                $infoList,
                $infoList.", {$authObjectCode}",
                $content
            );
        }

        file_put_contents($seederPath, $content);
    }
}
