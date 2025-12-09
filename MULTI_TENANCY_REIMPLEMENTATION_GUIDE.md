# Multi-Tenancy Re-Implementation Guide

## Overview

This guide provides the correct order for implementing the Database Per Tenant multi-tenancy system. Follow these steps sequentially, as each step builds upon the previous ones.

## Prerequisites

Before starting, ensure you have:
- Laravel application with module structure
- Separate database connections for each module (authentication, authorization, todo, config_transports)
- All existing modules working correctly
- Backup of existing databases

## Implementation Order

### Phase 1: Foundation (System Database & Configuration)

#### Step 1.1: Add System Database Connection

**File:** `config/database.php`

**Action:** Add new `system` connection to the `connections` array:

```php
'system' => [
    'driver' => env('SYSTEM_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
    'url' => env('SYSTEM_DB_URL'),
    'host' => env('SYSTEM_DB_HOST', env('DB_HOST', '127.0.0.1')),
    'port' => env('SYSTEM_DB_PORT', env('DB_PORT', '3306')),
    'database' => env('SYSTEM_DB_DATABASE', database_path('system.sqlite')),
    'username' => env('SYSTEM_DB_USERNAME', env('DB_USERNAME', 'root')),
    'password' => env('SYSTEM_DB_PASSWORD', env('DB_PASSWORD', '')),
    'charset' => env('SYSTEM_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
    'collation' => env('SYSTEM_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'foreign_key_constraints' => env('SYSTEM_DB_FOREIGN_KEYS', true),
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

**Why First:** This connection is needed for the Tenant model to store tenant registry data.

**Verification:**
```bash
php artisan tinker
>>> config('database.connections.system.database')
# Should show path to system.sqlite
```

---

#### Step 1.2: Create Tenancy Configuration File

**File:** `config/tenancy.php`

**Action:** Create new configuration file:

```php
<?php

return [
    'resolution_strategy' => [
        'subdomain',
        'domain',
        'header',
        'session',
    ],

    'database_path' => base_path('tenants'),

    'default_tenant_id' => env('DEFAULT_TENANT_ID', null),

    'strict_isolation' => env('TENANT_STRICT_ISOLATION', true),
];
```

**Why Second:** Provides configuration that other components will reference.

**Verification:**
```bash
php artisan tinker
>>> config('tenancy.database_path')
# Should show tenants directory path
```

---

### Phase 2: Core Models & Database

#### Step 2.1: Create Tenants Migration

**Command:**
```bash
php artisan make:migration create_tenants_table --no-interaction
```

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_tenants_table.php`

**Action:** Update migration to create tenants table on `system` connection:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('system')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique()->nullable();
            $table->string('subdomain')->unique()->nullable();
            $table->string('database_prefix')->unique();
            $table->string('status')->default('active');
            $table->string('plan')->default('basic');
            $table->integer('max_users')->default(10);
            $table->timestamp('expires_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('domain');
            $table->index('subdomain');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('system')->dropIfExists('tenants');
    }
};
```

**Why Third:** Creates the database structure that the Tenant model will use.

**Verification:**
```bash
php artisan migrate --database=system
# Should create tenants table successfully
```

---

#### Step 2.2: Create Tenant Model

**Command:**
```bash
php artisan make:model Tenant --no-interaction
```

**File:** `app/Models/Tenant.php`

**Action:** Implement Tenant model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $connection = 'system';

    protected $fillable = [
        'name',
        'domain',
        'subdomain',
        'database_prefix',
        'status',
        'plan',
        'max_users',
        'expires_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'settings' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function getDatabasePath(string $connection): string
    {
        return base_path("tenants/{$this->id}/{$connection}.sqlite");
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    protected static function newFactory()
    {
        return \Database\Factories\TenantFactory::new();
    }
}
```

**Why Fourth:** Model is needed by TenantService and other components.

**Verification:**
```bash
php artisan tinker
>>> App\Models\Tenant::count()
# Should return 0 (no tenants yet)
```

---

#### Step 2.3: Create Tenant Factory

**Command:**
```bash
php artisan make:factory TenantFactory --model=Tenant --no-interaction
```

**File:** `database/factories/TenantFactory.php`

**Action:** Implement factory:

```php
<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        $subdomain = str()->slug($name);

        return [
            'name' => $name,
            'domain' => $this->faker->optional()->domainName(),
            'subdomain' => $subdomain,
            'database_prefix' => 'tenant_'.time().'_'.$this->faker->unique()->randomNumber(4),
            'status' => 'active',
            'plan' => $this->faker->randomElement(['basic', 'premium', 'enterprise']),
            'max_users' => $this->faker->numberBetween(10, 1000),
            'expires_at' => null,
            'settings' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }
}
```

**Why Fifth:** Needed for testing and development.

**Verification:**
```bash
php artisan tinker
>>> App\Models\Tenant::factory()->create()
# Should create a tenant successfully
```

---

### Phase 3: Tenant Service (Core Logic)

#### Step 3.1: Create TenantService

**File:** `app/Services/TenantService.php`

**Action:** Create service with tenant resolution and connection management:

```php
<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class TenantService
{
    protected ?Tenant $currentTenant = null;

    public function getCurrentTenant(): ?Tenant
    {
        if ($this->currentTenant) {
            return $this->currentTenant;
        }

        $tenant = $this->resolveFromDomain()
            ?? $this->resolveFromSubdomain()
            ?? $this->resolveFromHeader()
            ?? $this->resolveFromSession()
            ?? $this->resolveFromDefault();

        if ($tenant && $tenant->isActive()) {
            $this->currentTenant = $tenant;
            return $tenant;
        }

        return null;
    }

    public function setCurrentTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
        app()->instance('tenant', $tenant);
        $this->configureTenantConnections($tenant);
    }

    public function configureTenantConnections(Tenant $tenant): void
    {
        $connections = [
            'authentication',
            'authorization',
            'todo',
            'config_transports',
        ];

        foreach ($connections as $connection) {
            $databasePath = $tenant->getDatabasePath($connection);
            $directory = dirname($databasePath);
            
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            if (!File::exists($databasePath)) {
                File::put($databasePath, '');
            }

            Config::set("database.connections.{$connection}.database", $databasePath);
        }

        foreach ($connections as $connection) {
            \Illuminate\Support\Facades\DB::purge($connection);
        }
    }

    protected function resolveFromDomain(): ?Tenant
    {
        $domain = request()->getHost();
        return Tenant::where('domain', $domain)->active()->first();
    }

    protected function resolveFromSubdomain(): ?Tenant
    {
        $host = request()->getHost();
        $parts = explode('.', $host);
        
        if (count($parts) >= 3) {
            $subdomain = $parts[0];
            return Tenant::where('subdomain', $subdomain)->active()->first();
        }

        return null;
    }

    protected function resolveFromHeader(): ?Tenant
    {
        $tenantId = request()->header('X-Tenant-ID');
        if ($tenantId) {
            return Tenant::where('id', $tenantId)->active()->first();
        }
        return null;
    }

    protected function resolveFromSession(): ?Tenant
    {
        $tenantId = session('tenant_id');
        if ($tenantId) {
            return Tenant::where('id', $tenantId)->active()->first();
        }
        return null;
    }

    protected function resolveFromDefault(): ?Tenant
    {
        $defaultTenantId = config('tenancy.default_tenant_id');
        if ($defaultTenantId) {
            return Tenant::where('id', $defaultTenantId)->active()->first();
        }
        return null;
    }

    public function createTenant(array $data): Tenant
    {
        if (!isset($data['database_prefix'])) {
            $data['database_prefix'] = 'tenant_'.time();
        }

        $tenant = Tenant::create($data);
        $this->initializeTenantDatabases($tenant);
        return $tenant;
    }

    public function initializeTenantDatabases(Tenant $tenant): void
    {
        $connections = [
            'authentication',
            'authorization',
            'todo',
            'config_transports',
        ];

        foreach ($connections as $connection) {
            $databasePath = $tenant->getDatabasePath($connection);
            $directory = dirname($databasePath);
            File::makeDirectory($directory, 0755, true);
            File::put($databasePath, '');
        }

        $this->runTenantMigrations($tenant);
    }

    public function runTenantMigrations(Tenant $tenant, bool $fresh = false): void
    {
        $this->setCurrentTenant($tenant);
        $command = $fresh ? 'migrate:fresh' : 'migrate';

        \Artisan::call($command, [
            '--database' => 'authentication',
            '--path' => 'Modules/Authentication/database/migrations',
        ]);

        \Artisan::call($command, [
            '--database' => 'authorization',
            '--path' => 'Modules/Authorization/database/migrations',
        ]);

        \Artisan::call($command, [
            '--database' => 'todo',
            ' '--path' => 'Modules/Todo/database/migrations',
        ]);

        \Artisan::call($command, [
            '--database' => 'config_transports',
            '--path' => 'Modules/ConfigTransports/database/migrations',
        ]);
    }

    public function deleteTenant(Tenant $tenant): void
    {
        $tenantDirectory = base_path("tenants/{$tenant->id}");
        if (File::exists($tenantDirectory)) {
            File::deleteDirectory($tenantDirectory);
        }
        $tenant->delete();
    }
}
```

**Why Sixth:** Core service that handles all tenant operations. Must be created before middleware and commands.

**Verification:**
```bash
php artisan tinker
>>> $service = app(\App\Services\TenantService::class);
>>> $tenant = \App\Models\Tenant::factory()->create();
>>> $service->setCurrentTenant($tenant);
>>> config('database.connections.authentication.database')
# Should show tenant-specific path
```

---

### Phase 4: Middleware & Request Handling

#### Step 4.1: Create IdentifyTenant Middleware

**File:** `app/Http/Middleware/IdentifyTenant.php`

**Action:** Create middleware:

```php
<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tenant identification for tenant management routes
        if ($request->is('admin/tenants*')) {
            return $next($request);
        }

        $tenantService = app(TenantService::class);
        $tenant = $tenantService->getCurrentTenant();

        if (!$tenant) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tenant not found or inactive',
                ], 404);
            }
            abort(404, 'Tenant not found');
        }

        $tenantService->setCurrentTenant($tenant);
        return $next($request);
    }
}
```

**Why Seventh:** Middleware needs TenantService to work. Must be registered before routes are processed.

**Verification:** (After registering in Step 4.2)
```bash
# Test with default tenant
php artisan tinker
>>> config(['tenancy.default_tenant_id' => 1]);
>>> $tenant = \App\Models\Tenant::factory()->create();
>>> # Visit any route - should work
```

---

#### Step 4.2: Register Middleware

**File:** `bootstrap/app.php`

**Action:** Register middleware in web and api groups:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \App\Http\Middleware\IdentifyTenant::class,
    ]);

    $middleware->api(append: [
        \App\Http\Middleware\IdentifyTenant::class,
    ]);
})
```

**Why Eighth:** Middleware must be registered to take effect on requests.

**Verification:**
```bash
# Check middleware is registered
php artisan route:list | grep admin
# Should show routes with middleware applied
```

---

### Phase 5: Artisan Commands

#### Step 5.1: Create TenantCreateCommand

**Command:**
```bash
php artisan make:command TenantCreateCommand --no-interaction
```

**File:** `app/Console/Commands/TenantCreateCommand.php`

**Action:** Implement command:

```php
<?php

namespace App\Console\Commands;

use App\Services\TenantService;
use Illuminate\Console\Command;

class TenantCreateCommand extends Command
{
    protected $signature = 'tenant:create 
                            {name : Tenant name}
                            {--domain= : Custom domain}
                            {--subdomain= : Subdomain}
                            {--plan=basic : Plan type}
                            {--max-users=10 : Maximum users}';

    protected $description = 'Create a new tenant';

    public function handle(TenantService $tenantService): int
    {
        $name = $this->argument('name');
        $domain = $this->option('domain');
        $subdomain = $this->option('subdomain') ?? str()->slug($name);
        $plan = $this->option('plan');
        $maxUsers = (int) $this->option('max-users');

        if (!$domain && !$subdomain) {
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
```

**Why Ninth:** Commands depend on TenantService. Create command first as it's the most fundamental.

**Verification:**
```bash
php artisan tenant:create "Test Tenant" --subdomain=test
# Should create tenant and initialize databases
```

---

#### Step 5.2: Create TenantMigrateCommand

**Command:**
```bash
php artisan make:command TenantMigrateCommand --no-interaction
```

**File:** `app/Console/Commands/TenantMigrateCommand.php`

**Action:** Implement command:

```php
<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Console\Command;

class TenantMigrateCommand extends Command
{
    protected $signature = 'tenant:migrate 
                            {tenant? : Tenant ID or "all" for all tenants}
                            {--fresh : Drop all tables and re-run migrations}
                            {--seed : Seed the database after migrating}';

    protected $description = 'Run migrations for tenant(s)';

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

            $connections = [
                'authentication' => 'Modules/Authentication/database/migrations',
                'authorization' => 'Modules/Authorization/database/migrations',
                'todo' => 'Modules/Todo/database/migrations',
                'config_transports' => 'Modules/ConfigTransports/database/migrations',
            ];

            foreach ($connections as $connection => $path) {
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

            $this->info("✓ Migrated: {$tenant->name}");
        }

        return Command::SUCCESS;
    }
}
```

**Why Tenth:** Needed to run migrations for tenants after creation.

**Verification:**
```bash
php artisan tenant:migrate 1
# Should run migrations for all connections
```

---

#### Step 5.3: Create TenantSeedCommand

**Command:**
```bash
php artisan make:command TenantSeedCommand --no-interaction
```

**File:** `app/Console/Commands/TenantSeedCommand.php`

**Action:** Implement command:

```php
<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Console\Command;

class TenantSeedCommand extends Command
{
    protected $signature = 'tenant:seed 
                            {tenant : Tenant ID}
                            {--class= : Specific seeder class}';

    protected $description = 'Seed database for a tenant';

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
```

**Why Eleventh:** Useful for seeding tenant data.

**Verification:**
```bash
php artisan tenant:seed 1
# Should seed tenant database
```

---

#### Step 5.4: Create TenantListCommand

**Command:**
```bash
php artisan make:command TenantListCommand --no-interaction
```

**File:** `app/Console/Commands/TenantListCommand.php`

**Action:** Implement command:

```php
<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantListCommand extends Command
{
    protected $signature = 'tenant:list';
    protected $description = 'List all tenants';

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
```

**Why Twelfth:** Utility command for listing tenants.

**Verification:**
```bash
php artisan tenant:list
# Should display tenants table
```

---

#### Step 5.5: Create MigrateToMultiTenant Command

**Command:**
```bash
php artisan make:command MigrateToMultiTenant --no-interaction
```

**File:** `app/Console/Commands/MigrateToMultiTenant.php`

**Action:** Implement command:

```php
<?php

namespace App\Console\Commands;

use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateToMultiTenant extends Command
{
    protected $signature = 'tenant:migrate-existing 
                            {--tenant-name=Default : Name for the default tenant}';

    protected $description = 'Migrate existing single-tenant data to multi-tenant structure';

    public function handle(TenantService $tenantService): int
    {
        $this->info('Migrating existing data to multi-tenant structure...');

        $tenant = $tenantService->createTenant([
            'name' => $this->option('tenant-name'),
            'subdomain' => 'default',
            'database_prefix' => 'tenant_1',
            'status' => 'active',
            'plan' => 'enterprise',
        ]);

        $connections = [
            'authentication' => 'Modules/Authentication/database/authentication.sqlite',
            'authorization' => 'Modules/Authorization/database/authorization.sqlite',
            'todo' => 'Modules/Todo/database/todo.sqlite',
            'config_transports' => 'Modules/ConfigTransports/database/config_transports.sqlite',
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
```

**Why Thirteenth:** Migration command for existing data. Should be created last among commands.

**Verification:**
```bash
# Backup existing databases first!
php artisan tenant:migrate-existing --tenant-name="My Company"
# Should copy existing databases to tenant directory
```

---

### Phase 6: Admin UI

#### Step 6.1: Create TenantController

**Command:**
```bash
php artisan make:controller Admin/TenantController --no-interaction
```

**File:** `app/Http/Controllers/Admin/TenantController.php`

**Action:** Implement controller:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function index(): View
    {
        $tenants = Tenant::latest()->paginate(15);
        return view('admin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        return view('admin.tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'subdomain' => 'nullable|string|max:255|unique:tenants,subdomain',
            'plan' => 'required|in:basic,premium,enterprise',
            'max_users' => 'required|integer|min:1',
        ]);

        $tenant = $this->tenantService->createTenant($validated);

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant created successfully.');
    }

    public function show(Tenant $tenant): View
    {
        return view('admin.tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant): View
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:tenants,domain,'.$tenant->id,
            'subdomain' => 'nullable|string|max:255|unique:tenants,subdomain,'.$tenant->id,
            'plan' => 'required|in:basic,premium,enterprise',
            'max_users' => 'required|integer|min:1',
            'status' => 'required|in:active,suspended,expired',
        ]);

        $tenant->update($validated);

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->tenantService->deleteTenant($tenant);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }
}
```

**Why Fourteenth:** Controller depends on TenantService and Tenant model.

**Verification:** (After adding routes in Step 6.3)
```bash
# Visit /admin/tenants - should show tenant list
```

---

#### Step 6.2: Create Tenant Views

**Directory:** `resources/views/admin/tenants/`

**Files to create:**
- `index.blade.php` - List tenants
- `create.blade.php` - Create tenant form
- `show.blade.php` - View tenant details
- `edit.blade.php` - Edit tenant form

**Action:** Create views following the pattern from `Modules/ConfigTransports/resources/views/admin/` (use same layout and styling).

**Why Fifteenth:** Views depend on controller and routes.

**Verification:** (After adding routes)
```bash
# Visit /admin/tenants/create - should show form
```

---

#### Step 6.3: Add Tenant Routes

**File:** `routes/web.php`

**Action:** Add routes after existing routes:

```php
Route::middleware(['web', 'auth'])->prefix('admin/tenants')->name('admin.tenants.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\TenantController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\TenantController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\TenantController::class, 'store'])->name('store');
    Route::get('/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'show'])->name('show');
    Route::get('/{tenant}/edit', [\App\Http\Controllers\Admin\TenantController::class, 'edit'])->name('edit');
    Route::put('/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'update'])->name('update');
    Route::delete('/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'destroy'])->name('destroy');
});
```

**Why Sixteenth:** Routes connect controller to URLs. Must be after controller and views.

**Verification:**
```bash
php artisan route:list | grep tenants
# Should show all tenant routes
```

---

### Phase 7: Module Integration

#### Step 7.1: Update ConfigTransports - TransportRecorder

**File:** `Modules/ConfigTransports/app/Services/TransportRecorder.php`

**Action:** Update `generateTransportNumber()` method:

```php
protected function generateTransportNumber(): string
{
    $tenant = app('tenant');
    $envPrefix = strtoupper(config('system.environment_role', 'dev'));
    $tenantPrefix = $tenant ? "{$tenant->id}_" : '';

    $lastNumber = TransportRequest::where('number', 'like', $tenantPrefix.$envPrefix.'K%')
        ->orderBy('number', 'desc')
        ->value('number');

    if ($lastNumber) {
        $fullPrefix = $tenantPrefix.$envPrefix.'K';
        $sequence = (int) substr($lastNumber, strlen($fullPrefix));
        $sequence++;
    } else {
        $sequence = 900001;
    }

    return $tenantPrefix.$envPrefix.'K'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
}
```

**Why Seventeenth:** Module integration should happen after core infrastructure is working.

**Verification:**
```bash
# Create a transport request and verify number includes tenant ID
```

---

#### Step 7.2: Update ConfigTransports - TransportRequestController

**File:** `Modules/ConfigTransports/app/Http/Controllers/TransportRequestController.php`

**Action:** Update `generateTransportNumber()` method (same as Step 7.1).

**Why Eighteenth:** Same reason as Step 7.1.

**Verification:**
```bash
# Create transport request via UI and verify number format
```

---

### Phase 8: Testing

#### Step 8.1: Create Tenant Test

**File:** `tests/Feature/TenantTest.php`

**Action:** Create comprehensive tenant tests (see existing file for reference).

**Why Nineteenth:** Tests should be created after all functionality is implemented.

**Verification:**
```bash
php artisan test tests/Feature/TenantTest.php
# All tests should pass
```

---

#### Step 8.2: Update Existing Tests

**Files to update:**
- `Modules/Authentication/tests/Feature/AuthenticationTest.php`
- `Modules/ConfigTransports/tests/Feature/TransportRecorderTest.php`
- All other feature tests

**Action:** Add tenant context to `beforeEach()` hooks:

```php
beforeEach(function () {
    $tenant = Tenant::factory()->create();
    app(TenantService::class)->setCurrentTenant($tenant);
    
    // ... existing setup
});
```

**Why Twentieth:** Tests need tenant context to work with multi-tenant system.

**Verification:**
```bash
php artisan test
# All tests should pass
```

---

## Implementation Checklist

Use this checklist to track progress:

### Phase 1: Foundation
- [ ] Step 1.1: Add system database connection
- [ ] Step 1.2: Create tenancy configuration

### Phase 2: Core Models & Database
- [ ] Step 2.1: Create tenants migration
- [ ] Step 2.2: Create Tenant model
- [ ] Step 2.3: Create Tenant factory

### Phase 3: Tenant Service
- [ ] Step 3.1: Create TenantService

### Phase 4: Middleware & Request Handling
- [ ] Step 4.1: Create IdentifyTenant middleware
- [ ] Step 4.2: Register middleware

### Phase 5: Artisan Commands
- [ ] Step 5.1: Create TenantCreateCommand
- [ ] Step 5.2: Create TenantMigrateCommand
- [ ] Step 5.3: Create TenantSeedCommand
- [ ] Step 5.4: Create TenantListCommand
- [ ] Step 5.5: Create MigrateToMultiTenant command

### Phase 6: Admin UI
- [ ] Step 6.1: Create TenantController
- [ ] Step 6.2: Create tenant views
- [ ] Step 6.3: Add tenant routes

### Phase 7: Module Integration
- [ ] Step 7.1: Update ConfigTransports TransportRecorder
- [ ] Step 7.2: Update ConfigTransports TransportRequestController

### Phase 8: Testing
- [ ] Step 8.1: Create Tenant test
- [ ] Step 8.2: Update existing tests

## Critical Dependencies

**Must be done in order:**
1. System database connection → Tenant model → TenantService
2. TenantService → Middleware → Commands
3. TenantService → Controller → Views → Routes
4. Core infrastructure → Module updates → Tests

## Verification After Each Phase

### After Phase 1
```bash
php artisan tinker
>>> config('database.connections.system.database')
>>> config('tenancy.database_path')
```

### After Phase 2
```bash
php artisan migrate --database=system
php artisan tinker
>>> App\Models\Tenant::factory()->create()
```

### After Phase 3
```bash
php artisan tinker
>>> $service = app(\App\Services\TenantService::class);
>>> $tenant = \App\Models\Tenant::factory()->create();
>>> $service->setCurrentTenant($tenant);
>>> config('database.connections.authentication.database')
```

### After Phase 4
```bash
# Set default tenant in .env
DEFAULT_TENANT_ID=1
# Visit any route - should work
```

### After Phase 5
```bash
php artisan tenant:create "Test" --subdomain=test
php artisan tenant:list
php artisan tenant:migrate 1
```

### After Phase 6
```bash
# Visit /admin/tenants
# Create tenant via UI
# Verify tenant created
```

### After Phase 7
```bash
# Create transport request
# Verify number includes tenant ID
```

### After Phase 8
```bash
php artisan test
# All tests should pass
```

## Common Issues & Solutions

### Issue: Tenant not found
**Solution:** Set `DEFAULT_TENANT_ID=1` in `.env` for development

### Issue: Database connection errors
**Solution:** Ensure tenant databases exist: `php artisan tenant:migrate {id}`

### Issue: Middleware not working
**Solution:** Clear config cache: `php artisan config:clear`

### Issue: Tests failing
**Solution:** Ensure all tests create tenant in `beforeEach()`

## Migration from Single-Tenant

If migrating existing application:

1. **Backup all databases first!**
2. Run system migration: `php artisan migrate --database=system`
3. Migrate existing data: `php artisan tenant:migrate-existing`
4. Set default tenant: `DEFAULT_TENANT_ID=1` in `.env`
5. Test thoroughly before deploying

## Summary

This guide provides the exact order for implementing multi-tenancy. Follow each phase sequentially, verify after each step, and test thoroughly before moving to the next phase. The key is to build from the foundation (database, model, service) up to the UI and integration layers.

