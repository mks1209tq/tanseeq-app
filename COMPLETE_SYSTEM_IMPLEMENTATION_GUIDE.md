# Complete System Implementation Guide

## Overview

This guide provides the **correct order** for implementing the entire system from scratch: Laravel → Nwidart Modules → Database Separation → Core Modules → Config Transport System → Multi-Tenancy.

**Critical Order:** Nwidart/Laravel-Modules **MUST** be implemented before multi-tenancy, as multi-tenancy depends on the module structure.

---

## Phase 1: Fresh Laravel Installation

### Step 1.1: Create Laravel Project

```bash
composer create-project laravel/laravel my-app
cd my-app
```

### Step 1.2: Basic Configuration

- Set up `.env` file
- Generate application key: `php artisan key:generate`
- Configure basic settings

**Why First:** Foundation for everything else.

---

## Phase 2: Nwidart/Laravel-Modules Setup

### Step 2.1: Install Nwidart/Laravel-Modules Package

```bash
composer require nwidart/laravel-modules
```

### Step 2.2: Publish Module Configuration

```bash
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"
```

**File:** `config/modules.php` will be created.

### Step 2.3: Configure Module Paths

**File:** `config/modules.php`

Verify/update:
- `'namespace' => 'Modules'`
- `'modules' => base_path('Modules')`
- `'assets' => public_path('modules')`

### Step 2.4: Register Module Service Provider

**File:** `bootstrap/providers.php` (Laravel 11+)

Add:
```php
\Nwidart\Modules\LaravelModulesServiceProvider::class,
```

Or in `config/app.php` (Laravel 10):
```php
'providers' => [
    // ...
    Nwidart\Modules\LaravelModulesServiceProvider::class,
],
```

### Step 2.5: Test Module System

```bash
php artisan module:list
# Should show empty list (no modules yet)
```

**Why Second:** Module system must be in place before creating any modules. This is the foundation for all module-based features.

**Verification:**
```bash
php artisan module:make TestModule
php artisan module:list
# Should show TestModule
php artisan module:delete TestModule
```

---

## Phase 3: Database Separation Setup

### Step 3.1: Configure Separate Database Connections

**File:** `config/database.php`

Add connections for each module:

```php
'connections' => [
    // ... default connection ...
    
    'authentication' => [
        'driver' => env('AUTHENTICATION_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
        'database' => env('AUTHENTICATION_DB_DATABASE', base_path('Modules/Authentication/database/authentication.sqlite')),
        'username' => env('AUTHENTICATION_DB_USERNAME', env('DB_USERNAME', 'root')),
        'password' => env('AUTHENTICATION_DB_PASSWORD', env('DB_PASSWORD', '')),
        'charset' => env('AUTHENTICATION_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
        'collation' => env('AUTHENTICATION_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'foreign_key_constraints' => env('AUTHENTICATION_DB_FOREIGN_KEYS', true),
    ],
    
    'authorization' => [
        'driver' => env('AUTHORIZATION_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
        'database' => env('AUTHORIZATION_DB_DATABASE', base_path('Modules/Authorization/database/authorization.sqlite')),
        'username' => env('AUTHORIZATION_DB_USERNAME', env('DB_USERNAME', 'root')),
        'password' => env('AUTHORIZATION_DB_PASSWORD', env('DB_PASSWORD', '')),
        'charset' => env('AUTHORIZATION_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
        'collation' => env('AUTHORIZATION_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'foreign_key_constraints' => env('AUTHORIZATION_DB_FOREIGN_KEYS', true),
    ],
    
    'todo' => [
        'driver' => env('TODO_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
        'database' => env('TODO_DB_DATABASE', base_path('Modules/Todo/database/todo.sqlite')),
        'username' => env('TODO_DB_USERNAME', env('DB_USERNAME', 'root')),
        'password' => env('TODO_DB_PASSWORD', env('DB_PASSWORD', '')),
        'charset' => env('TODO_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
        'collation' => env('TODO_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'foreign_key_constraints' => env('TODO_DB_FOREIGN_KEYS', true),
    ],
],
```

**Why Third:** Database connections must be configured before creating modules that use them.

**Verification:**
```bash
php artisan tinker
>>> config('database.connections.authentication.database')
# Should show path
```

---

## Phase 4: Create Core Modules

### Step 4.1: Create UI Module (Base Module)

```bash
php artisan module:make UI
```

**Why First Module:** Provides base layouts and components that other modules will use.

**Files to create:**
- `Modules/UI/resources/views/components/layouts/master.blade.php`
- `Modules/UI/resources/views/components/layouts/app.blade.php`
- Shared Blade components

### Step 4.2: Create Authentication Module

```bash
php artisan module:make Authentication
```

**Implementation Steps:**
1. Create migrations for `users`, `password_reset_tokens`, `sessions`, `auth_settings`
2. Create models: `User`, `AuthSetting`
3. Create controllers, routes, views
4. Set models to use `authentication` connection
5. Run migrations: `php artisan migrate --database=authentication --path=Modules/Authentication/database/migrations`

**Why Second:** Authentication is foundational - other modules depend on users.

### Step 4.3: Create Authorization Module

```bash
php artisan module:make Authorization
```

**Implementation Steps:**
1. Create migrations for `roles`, `role_user`, `auth_objects`, `auth_object_fields`, `role_authorizations`, `role_authorization_fields`
2. Create models: `Role`, `AuthObject`, `AuthObjectField`, `RoleAuthorization`, `RoleAuthorizationField`
3. Create authorization service
4. Set models to use `authorization` connection
5. Run migrations: `php artisan migrate --database=authorization --path=Modules/Authorization/database/migrations`

**Why Third:** Authorization depends on Authentication (users).

### Step 4.4: Create Todo Module (Demo)

```bash
php artisan module:make Todo
```

**Implementation Steps:**
1. Create migration for `todos`
2. Create model: `Todo`
3. Create controllers, routes, views
4. Set model to use `todo` connection
5. Run migrations: `php artisan migrate --database=todo --path=Modules/Todo/database/migrations`

**Why Fourth:** Demo module for testing authorization system.

### Step 4.5: Update Composer Autoload

**File:** `composer.json`

Add module autoloading:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Modules\\Authentication\\": "Modules/Authentication/app/",
        "Modules\\Authorization\\": "Modules/Authorization/app/",
        "Modules\\Todo\\": "Modules/Todo/app/",
        "Modules\\UI\\": "Modules/UI/app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
}
```

Run: `composer dump-autoload`

**Why Fifth:** Ensures modules are autoloaded correctly.

---

## Phase 5: Config Transport System

### Step 5.1: Create ConfigTransports Module

```bash
php artisan module:make ConfigTransports
```

### Step 5.2: Add ConfigTransports Database Connection

**File:** `config/database.php`

Add:
```php
'config_transports' => [
    'driver' => env('CONFIG_TRANSPORTS_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
    'url' => env('CONFIG_TRANSPORTS_DB_URL'),
    'host' => env('CONFIG_TRANSPORTS_DB_HOST', env('DB_HOST', '127.0.0.1')),
    'port' => env('CONFIG_TRANSPORTS_DB_PORT', env('DB_PORT', '3306')),
    'database' => env('CONFIG_TRANSPORTS_DB_DATABASE', base_path('Modules/ConfigTransports/database/config_transports.sqlite')),
    'username' => env('CONFIG_TRANSPORTS_DB_USERNAME', env('DB_USERNAME', 'root')),
    'password' => env('CONFIG_TRANSPORTS_DB_PASSWORD', env('DB_PASSWORD', '')),
    'charset' => env('CONFIG_TRANSPORTS_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
    'collation' => env('CONFIG_TRANSPORTS_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'foreign_key_constraints' => env('CONFIG_TRANSPORTS_DB_FOREIGN_KEYS', true),
],
```

### Step 5.3: Implement Transport System

Follow the steps in `SAP-style Config Transport System.plan.md`:

1. Create `Transportable` interface
2. Create `IsTransportable` trait
3. Create models: `TransportRequest`, `TransportItem`, `TransportImportLog`
4. Create migrations
5. Create `TransportRecorder` service
6. Create export/import commands
7. Make Authorization models transportable

**Why Fifth:** Transport system is a module itself and depends on existing modules (Authorization) to transport.

**Verification:**
```bash
php artisan transports:export DEVK900001
# Should export transport
```

---

## Phase 6: Multi-Tenancy Implementation

### Step 6.1: Add System Database Connection

**File:** `config/database.php`

Add `system` connection:

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
],
```

**Why First:** Needed for Tenant model.

### Step 6.2: Create Tenancy Configuration

**File:** `config/tenancy.php`

Create configuration file:

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

### Step 6.3: Create Tenant Model & Migration

**Command:**
```bash
php artisan make:migration create_tenants_table --no-interaction
php artisan make:model Tenant --no-interaction
php artisan make:factory TenantFactory --model=Tenant --no-interaction
```

**Files:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_tenants_table.php`
- `app/Models/Tenant.php`
- `database/factories/TenantFactory.php`

See `MULTI_TENANCY_REIMPLEMENTATION_GUIDE.md` for detailed implementation.

Run migration: `php artisan migrate --database=system`

### Step 6.4: Create TenantService

**File:** `app/Services/TenantService.php`

This service:
- Resolves tenants (domain, subdomain, header, session, default)
- Configures database connections dynamically per tenant
- Manages tenant lifecycle

**Why Critical:** This is the core of multi-tenancy. It changes module database paths based on tenant.

See `MULTI_TENANCY_REIMPLEMENTATION_GUIDE.md` Phase 3 for full implementation.

### Step 6.5: Create IdentifyTenant Middleware

**File:** `app/Http/Middleware/IdentifyTenant.php`

**File:** `bootstrap/app.php`

Register middleware:

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

See `MULTI_TENANCY_REIMPLEMENTATION_GUIDE.md` Phase 4 for full implementation.

### Step 6.6: Create Tenant Management Commands

**Commands to create:**
- `TenantCreateCommand`
- `TenantMigrateCommand`
- `TenantSeedCommand`
- `TenantListCommand`
- `MigrateToMultiTenant`

See `MULTI_TENANCY_REIMPLEMENTATION_GUIDE.md` Phase 5 for full implementation.

### Step 6.7: Create Tenant Admin UI

**Files:**
- `app/Http/Controllers/Admin/TenantController.php`
- `resources/views/admin/tenants/index.blade.php`
- `resources/views/admin/tenants/create.blade.php`
- `resources/views/admin/tenants/show.blade.php`
- `resources/views/admin/tenants/edit.blade.php`
- `routes/web.php` - Add tenant routes

See `MULTI_TENANCY_REIMPLEMENTATION_GUIDE.md` Phase 6 for full implementation.

### Step 6.8: Update ConfigTransports for Multi-Tenancy

**Files:**
- `Modules/ConfigTransports/app/Services/TransportRecorder.php`
- `Modules/ConfigTransports/app/Http/Controllers/TransportRequestController.php`

Update `generateTransportNumber()` to include tenant ID:

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

### Step 6.9: Update Tests

**Files:**
- `tests/TestCase.php` - Add tenant setup
- `tests/Pest.php` - Configure test case
- All feature tests - Add tenant context

**File:** `tests/TestCase.php`

```php
<?php

namespace Tests;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test tenant for all tests
        $this->tenant = Tenant::factory()->create();
        app(TenantService::class)->setCurrentTenant($this->tenant);
    }
}
```

**Why Last:** Multi-tenancy wraps around existing modules. All modules must exist and work before adding tenant isolation.

---

## Implementation Checklist

### Phase 1: Laravel Base
- [ ] Fresh Laravel installation
- [ ] Basic configuration
- [ ] Environment setup

### Phase 2: Nwidart Modules
- [ ] Install `nwidart/laravel-modules`
- [ ] Publish configuration
- [ ] Register service provider
- [ ] Test module system

### Phase 3: Database Separation
- [ ] Configure `authentication` connection
- [ ] Configure `authorization` connection
- [ ] Configure `todo` connection
- [ ] Test connections

### Phase 4: Core Modules
- [ ] Create UI module
- [ ] Create Authentication module
- [ ] Create Authorization module
- [ ] Create Todo module
- [ ] Update composer autoload
- [ ] Run all migrations

### Phase 5: Config Transport System
- [ ] Create ConfigTransports module
- [ ] Configure `config_transports` connection
- [ ] Implement Transportable interface
- [ ] Create transport models
- [ ] Create TransportRecorder service
- [ ] Create export/import commands
- [ ] Make Authorization models transportable

### Phase 6: Multi-Tenancy
- [ ] Add `system` database connection
- [ ] Create tenancy configuration
- [ ] Create Tenant model & migration
- [ ] Create TenantService
- [ ] Create IdentifyTenant middleware
- [ ] Create tenant management commands
- [ ] Create tenant admin UI
- [ ] Update ConfigTransports for multi-tenancy
- [ ] Update all tests

---

## Critical Dependencies

**Must be done in this order:**

1. **Laravel Base** → **Nwidart Modules** → **Database Separation**
2. **Database Separation** → **Core Modules** (modules need connections)
3. **Core Modules** → **ConfigTransports** (transports Authorization data)
4. **All Modules** → **Multi-Tenancy** (tenancy wraps existing modules)

**Why Nwidart First:**
- Modules must exist before you can isolate them per tenant
- Multi-tenancy changes module database paths dynamically
- TenantService references module database paths: `Modules/Authentication/database/authentication.sqlite`
- Without modules, there's nothing to tenant-isolate

**Why Multi-Tenancy Last:**
- Multi-tenancy is a layer on top of existing modules
- It requires all modules to be working correctly
- It dynamically changes database connections that modules use
- Adding it too early would complicate module development

---

## Verification After Each Phase

### After Phase 2 (Nwidart)
```bash
php artisan module:list
php artisan module:make TestModule
php artisan module:delete TestModule
```

### After Phase 3 (Database Separation)
```bash
php artisan tinker
>>> config('database.connections.authentication.database')
>>> config('database.connections.authorization.database')
```

### After Phase 4 (Core Modules)
```bash
php artisan migrate --database=authentication --path=Modules/Authentication/database/migrations
php artisan migrate --database=authorization --path=Modules/Authorization/database/migrations
php artisan migrate --database=todo --path=Modules/Todo/database/migrations
# Visit routes - should work
```

### After Phase 5 (ConfigTransports)
```bash
php artisan transports:export DEVK900001
# Should export transport
```

### After Phase 6 (Multi-Tenancy)
```bash
php artisan tenant:create "Test" --subdomain=test
php artisan tenant:list
php artisan tenant:migrate 1
# Visit routes with tenant context - should work
```

---

## Common Mistakes to Avoid

### ❌ Wrong Order
1. Creating multi-tenancy before modules exist
2. Creating modules before Nwidart is configured
3. Creating modules before database connections are set up
4. Creating ConfigTransports before Authorization module exists

### ✅ Correct Order
1. Laravel → Nwidart → Database Connections
2. Database Connections → Modules
3. Modules → ConfigTransports
4. Everything → Multi-Tenancy

---

## Summary

**The correct implementation order is:**

1. **Laravel Base Application**
2. **Nwidart/Laravel-Modules** (Module system foundation)
3. **Database Separation** (Connection configuration)
4. **Core Modules** (Authentication, Authorization, Todo, UI)
5. **Config Transport System** (Transport module)
6. **Multi-Tenancy** (Tenant isolation layer)

**Key Principle:** Build from foundation up. Each layer depends on the previous one. Multi-tenancy is the final layer that wraps everything else.

---

## Next Steps

After completing all phases:
1. Run full test suite
2. Create documentation for each module
3. Set up CI/CD pipeline
4. Plan deployment strategy
5. Consider additional modules

---

## References

- **Multi-Tenancy Details:** `MULTI_TENANCY_REIMPLEMENTATION_GUIDE.md` (for multi-tenancy steps)
- **Config Transports:** `SAP-style Config Transport System.plan.md`
- **Database Separation:** `DATABASE_SEPARATION.md`
- **Module Structure:** `PROGRESS.md`

