# Multi-Tenancy Implementation - Database Per Tenant

## Overview

Multi-tenancy has been successfully implemented using the **Database Per Tenant** approach. Each tenant has completely isolated databases for all modules (authentication, authorization, todo, config_transports).

## What Was Implemented

### 1. Core Infrastructure

#### System Database Connection
- **File:** `config/database.php`
- Added `system` connection for tenant registry
- Stores tenant metadata in `database/system.sqlite`

#### Tenant Model
- **File:** `app/Models/Tenant.php`
- Manages tenant information (name, domain, subdomain, status, plan)
- Provides methods: `getDatabasePath()`, `isActive()`, `scopeActive()`
- Uses `system` database connection

#### Tenant Service
- **File:** `app/Services/TenantService.php`
- Handles tenant resolution (domain, subdomain, header, session, default)
- Configures database connections dynamically per tenant
- Manages tenant lifecycle (create, initialize, delete)
- Runs migrations for new tenants

#### Tenant Middleware
- **File:** `app/Http/Middleware/IdentifyTenant.php`
- Automatically identifies tenant on each request
- Configures database connections before request processing
- Excludes tenant management routes (admin only)

### 2. Tenant Management

#### Artisan Commands
- **`tenant:create`** - Create new tenant with database initialization
- **`tenant:migrate`** - Run migrations for tenant(s)
- **`tenant:seed`** - Seed database for a tenant
- **`tenant:list`** - List all tenants
- **`tenant:migrate-existing`** - Migrate existing single-tenant data

#### Admin Controller & Views
- **File:** `app/Http/Controllers/Admin/TenantController.php`
- **Views:** `resources/views/admin/tenants/`
- Full CRUD interface for tenant management
- Routes: `/admin/tenants`

### 3. Module Updates

#### ConfigTransports Module
- **Updated:** `TransportRecorder::generateTransportNumber()`
- **Updated:** `TransportRequestController::generateTransportNumber()`
- Transport numbers now include tenant ID prefix: `{TENANT_ID}_{ENV}K{SEQUENCE}`
- Example: `1_DEVK900001` (tenant 1, dev environment)

### 4. Testing

#### Tenant Factory
- **File:** `database/factories/TenantFactory.php`
- States: `active()`, `suspended()`, `expired()`

#### Test Updates
- **File:** `tests/Feature/TenantTest.php` - Comprehensive tenant tests
- Updated existing tests to use tenant context
- All tests now create a tenant before running

## Database Structure

### Before (Single Tenant)
```
Modules/
├── Authentication/database/authentication.sqlite
├── Authorization/database/authorization.sqlite
├── Todo/database/todo.sqlite
└── ConfigTransports/database/config_transports.sqlite
```

### After (Multi-Tenant)
```
database/
└── system.sqlite (tenant registry)

tenants/
├── 1/
│   ├── authentication.sqlite
│   ├── authorization.sqlite
│   ├── todo.sqlite
│   └── config_transports.sqlite
├── 2/
│   └── (same structure)
└── ...
```

## Tenant Resolution Strategy

The system tries to resolve tenant in this order:

1. **Subdomain** - `tenant1.example.com` → matches `subdomain = 'tenant1'`
2. **Domain** - `tenant1.com` → matches `domain = 'tenant1.com'`
3. **Header** - `X-Tenant-ID: 1` → matches `id = 1`
4. **Session** - `session('tenant_id')` → matches `id`
5. **Default** - `DEFAULT_TENANT_ID` env variable (development only)

## Usage Examples

### Create a Tenant

```bash
php artisan tenant:create "Acme Corp" --subdomain=acme --plan=premium --max-users=100
```

### Run Migrations for Tenant

```bash
# Single tenant
php artisan tenant:migrate 1

# All tenants
php artisan tenant:migrate all

# Fresh migration with seed
php artisan tenant:migrate 1 --fresh --seed
```

### Migrate Existing Data

```bash
php artisan tenant:migrate-existing --tenant-name="Default Tenant"
```

This creates a default tenant and copies existing databases.

### List Tenants

```bash
php artisan tenant:list
```

## Configuration

### Environment Variables

Add to `.env`:

```env
# System database (tenant registry)
SYSTEM_DB_DRIVER=sqlite
SYSTEM_DB_DATABASE=database/system.sqlite

# Default tenant for development (optional)
DEFAULT_TENANT_ID=1

# Tenant isolation
TENANT_STRICT_ISOLATION=true
```

### Tenancy Config

**File:** `config/tenancy.php`

- `resolution_strategy` - Order of tenant resolution methods
- `database_path` - Base path for tenant databases
- `default_tenant_id` - Fallback tenant for development
- `strict_isolation` - Enforce tenant boundaries

## Key Features

### ✅ Complete Data Isolation
- Each tenant has separate databases
- No risk of cross-tenant data access
- Perfect for compliance requirements

### ✅ Automatic Tenant Resolution
- Works with subdomains, domains, headers, or sessions
- Configurable resolution strategy
- Development fallback support

### ✅ Dynamic Connection Management
- Database connections configured per request
- No code changes needed in models
- Transparent to application code

### ✅ Tenant Lifecycle Management
- Create tenants with automatic database initialization
- Run migrations per tenant
- Delete tenants with data cleanup

### ✅ Transport System Integration
- Transport numbers include tenant context
- Transports are tenant-specific
- Export/import within tenant only

## Migration from Single-Tenant

### Step 1: Run System Migration

```bash
php artisan migrate --database=system
```

### Step 2: Migrate Existing Data

```bash
php artisan tenant:migrate-existing --tenant-name="Your Company"
```

This will:
- Create a default tenant
- Copy existing databases to tenant directory
- Preserve all existing data

### Step 3: Update Environment

Set `DEFAULT_TENANT_ID=1` in `.env` for development.

### Step 4: Test

Verify that:
- Existing data is accessible
- New tenants can be created
- Tenant isolation works correctly

## Testing

All tests automatically create a tenant context. Example:

```php
beforeEach(function () {
    $tenant = Tenant::factory()->create();
    app(TenantService::class)->setCurrentTenant($tenant);
});
```

## API Usage

### With Header

```bash
curl -H "X-Tenant-ID: 1" https://api.example.com/users
```

### With Subdomain

```bash
curl https://tenant1.example.com/users
```

## Admin Access

Tenant management routes (`/admin/tenants`) bypass tenant identification, allowing system administrators to manage tenants without being in a tenant context.

## Important Notes

1. **Email Uniqueness**: Emails are unique per tenant (not globally)
2. **Super Admin**: Super admin roles are per-tenant
3. **Transports**: Transport requests are tenant-specific
4. **Backups**: Backup each tenant's databases separately
5. **Migrations**: Run migrations per tenant, not globally

## Next Steps

1. **Run System Migration**: `php artisan migrate --database=system`
2. **Migrate Existing Data**: `php artisan tenant:migrate-existing`
3. **Create Additional Tenants**: `php artisan tenant:create`
4. **Configure DNS**: Set up subdomain routing if using subdomain strategy
5. **Update Documentation**: Document tenant-specific features for users

## Troubleshooting

### Tenant Not Found Error

- Check tenant exists: `php artisan tenant:list`
- Verify tenant is active: `status = 'active'`
- Check resolution strategy in `config/tenancy.php`
- Set `DEFAULT_TENANT_ID` for development

### Database Connection Errors

- Verify tenant databases exist in `tenants/{id}/`
- Check file permissions
- Ensure migrations have run: `php artisan tenant:migrate {id}`

### Migration Errors

- Run migrations per connection: `php artisan tenant:migrate {id}`
- Check migration paths are correct
- Verify database files are writable

