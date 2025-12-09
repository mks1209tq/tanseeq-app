# Microservices Migration Guide

## Overview

This guide explains how to migrate from the current **modular monolith** to **microservices** using Option 1 (Local Service Implementation). The migration is **configuration-driven** and requires **zero code changes** - you only need to update environment variables.

## Current State (Monolith Mode)

- All modules run in the same Laravel application
- `AppServiceProvider` automatically binds `LocalAuthenticationService` and `LocalAuthorizationService`
- Local services call API controllers directly (no HTTP overhead)
- Shared database
- Performance: ~1-2ms per service call

## Migration Process

### Step 1: Extract Modules to Separate Applications

For each module (Authentication, Authorization, Todo):

#### 1.1 Create New Laravel Applications

```bash
# Create new Laravel projects
composer create-project laravel/laravel authentication-service
composer create-project laravel/laravel authorization-service
composer create-project laravel/laravel todo-service
```

#### 1.2 Copy Module Files

**Authentication Service:**
```bash
# Copy module directory
cp -r Modules/Authentication/* authentication-service/app/

# Copy migrations
cp Modules/Authentication/database/migrations/* authentication-service/database/migrations/

# Copy routes
cp Modules/Authentication/routes/* authentication-service/routes/
```

**Authorization Service:**
```bash
cp -r Modules/Authorization/* authorization-service/app/
cp Modules/Authorization/database/migrations/* authorization-service/database/migrations/
cp Modules/Authorization/routes/* authorization-service/routes/
```

**Todo Service:**
```bash
cp -r Modules/Todo/* todo-service/app/
cp Modules/Todo/database/migrations/* todo-service/database/migrations/
cp Modules/Todo/routes/* todo-service/routes/
```

#### 1.3 Update Namespaces

For each service, update namespaces:

**Option A: Keep Module Structure**
- Keep `Modules\Authentication\*` namespaces
- Update autoloading in `composer.json`

**Option B: Flatten to App Namespace**
- Change `Modules\Authentication\*` → `App\*`
- Update all `use` statements
- Update autoloading

#### 1.4 Copy Shared Code

Copy these to **each service**:

```bash
# Service contracts
cp -r app/Contracts authentication-service/app/
cp -r app/Contracts authorization-service/app/
cp -r app/Contracts todo-service/app/

# DTOs
cp -r app/DTOs authentication-service/app/
cp -r app/DTOs authorization-service/app/
cp -r app/DTOs todo-service/app/

# Events
cp -r app/Events authentication-service/app/
cp -r app/Events authorization-service/app/
cp -r app/Events todo-service/app/

# Service clients (for microservice mode)
cp -r app/Services authentication-service/app/
cp -r app/Services authorization-service/app/
cp -r app/Services todo-service/app/
```

### Step 2: Configure Separate Databases

#### 2.1 Create Databases

```sql
CREATE DATABASE authentication_db;
CREATE DATABASE authorization_db;
CREATE DATABASE todo_db;
```

#### 2.2 Update Environment Files

**authentication-service/.env:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=authentication_db
DB_USERNAME=root
DB_PASSWORD=

# Service configuration
AUTHENTICATION_SERVICE_MODE=monolith
AUTHENTICATION_SERVICE_URL=http://authentication-service.test
```

**authorization-service/.env:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=authorization_db
DB_USERNAME=root
DB_PASSWORD=

# Points to Authentication service
AUTHENTICATION_SERVICE_MODE=microservice
AUTHENTICATION_SERVICE_URL=http://authentication-service.test

# This service itself
AUTHORIZATION_SERVICE_MODE=monolith
AUTHORIZATION_SERVICE_URL=http://authorization-service.test
```

**todo-service/.env:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=todo_db
DB_USERNAME=root
DB_PASSWORD=

# Points to other services
AUTHENTICATION_SERVICE_MODE=microservice
AUTHENTICATION_SERVICE_URL=http://authentication-service.test

AUTHORIZATION_SERVICE_MODE=microservice
AUTHORIZATION_SERVICE_URL=http://authorization-service.test

# This service itself
TODO_SERVICE_MODE=monolith
TODO_SERVICE_URL=http://todo-service.test
```

#### 2.3 Run Migrations

```bash
# In each service directory
cd authentication-service
php artisan migrate

cd authorization-service
php artisan migrate

cd todo-service
php artisan migrate
```

### Step 3: Automatic Service Switching

The `AppServiceProvider` automatically switches implementations based on configuration:

```php
// In AppServiceProvider::register()

$authMode = config('services.authentication.mode', 'monolith');

if ($authMode === 'monolith') {
    // Uses LocalAuthenticationService (calls controllers directly)
    $this->app->singleton(
        AuthenticationServiceInterface::class,
        LocalAuthenticationService::class
    );
} else {
    // Uses AuthenticationServiceClient (makes HTTP calls)
    $this->app->singleton(
        AuthenticationServiceInterface::class,
        AuthenticationServiceClient::class
    );
}
```

**What This Means:**
- When `AUTHENTICATION_SERVICE_MODE=monolith` → Uses `LocalAuthenticationService`
- When `AUTHENTICATION_SERVICE_MODE=microservice` → Uses `AuthenticationServiceClient`
- **Zero code changes needed** - just update `.env`

### Step 4: Deploy Services

#### 4.1 Deploy Each Service Independently

```bash
# Authentication Service
cd authentication-service
git clone <repo> .
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Authorization Service
cd authorization-service
git clone <repo> .
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Todo Service
cd todo-service
git clone <repo> .
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

#### 4.2 Configure Service URLs

Update service URLs based on your deployment:

**Development:**
```env
AUTHENTICATION_SERVICE_URL=http://authentication-service.test
AUTHORIZATION_SERVICE_URL=http://authorization-service.test
```

**Production:**
```env
AUTHENTICATION_SERVICE_URL=https://auth-api.yourdomain.com
AUTHORIZATION_SERVICE_URL=https://authz-api.yourdomain.com
```

**Docker/Kubernetes:**
```env
AUTHENTICATION_SERVICE_URL=http://authentication-service:8000
AUTHORIZATION_SERVICE_URL=http://authorization-service:8000
```

#### 4.3 Test Connectivity

```bash
# Test Authentication Service
curl http://authentication-service.test/api/v1/users/1

# Test Authorization Service
curl -X POST http://authorization-service.test/api/v1/authorizations/check \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "object_code": "SALES_ORDER_HEADER",
    "required_fields": {"ACTVT": "03"}
  }'
```

## Example: Gradual Migration

You can migrate **one service at a time** to minimize risk:

### Phase 1: Extract Authentication Service

**Current State:**
- Authentication module → separate Laravel app
- Authorization and Todo still in monolith
- Authorization/Todo use `LocalAuthenticationService` (monolith mode)

**Configuration:**
```env
# In main app (Authorization + Todo)
AUTHENTICATION_SERVICE_MODE=monolith  # Still local

# In Authentication service
AUTHENTICATION_SERVICE_MODE=monolith  # It IS the service
```

### Phase 2: Extract Authorization Service

**Current State:**
- Authentication service → separate (already done)
- Authorization module → separate Laravel app
- Todo still in monolith
- Todo uses HTTP clients for both services

**Configuration:**
```env
# In Todo service
AUTHENTICATION_SERVICE_MODE=microservice
AUTHENTICATION_SERVICE_URL=http://authentication-service.test

AUTHORIZATION_SERVICE_MODE=microservice
AUTHORIZATION_SERVICE_URL=http://authorization-service.test

# In Authorization service
AUTHENTICATION_SERVICE_MODE=microservice  # Points to Authentication
AUTHENTICATION_SERVICE_URL=http://authentication-service.test
AUTHORIZATION_SERVICE_MODE=monolith  # It IS the service
```

### Phase 3: Extract Todo Service

**Current State:**
- All services separate
- All communication via HTTP

**Configuration:**
```env
# In Todo service
AUTHENTICATION_SERVICE_MODE=microservice
AUTHENTICATION_SERVICE_URL=http://authentication-service.test

AUTHORIZATION_SERVICE_MODE=microservice
AUTHORIZATION_SERVICE_URL=http://authorization-service.test
```

## How Service Switching Works

### Before Migration (Monolith)

```
┌─────────────────────────────────────┐
│     Single Laravel Application      │
├─────────────────────────────────────┤
│  Todo Module                        │
│    ↓                                │
│  LocalAuthenticationService         │
│    ↓                                │
│  UserController (direct call)       │
│    ↓                                │
│  Authentication Module              │
└─────────────────────────────────────┘
```

**Code:**
```php
$authService = app(AuthenticationServiceInterface::class);
// Returns LocalAuthenticationService
// Calls UserController directly
$user = $authService->getUserById(1);
```

### After Migration (Microservices)

```
┌──────────────────────┐    HTTP     ┌──────────────────────┐
│   Todo Service       │─────────────>│ Authentication       │
│                      │             │ Service              │
│  Authentication      │             │                      │
│  ServiceClient       │             │  UserController      │
└──────────────────────┘             └──────────────────────┘
```

**Code:**
```php
$authService = app(AuthenticationServiceInterface::class);
// Returns AuthenticationServiceClient
// Makes HTTP GET http://auth-service/api/v1/users/1
$user = $authService->getUserById(1);
```

**Important:** The calling code **doesn't change** - only the implementation switches!

## Benefits of Option 1

### 1. Zero Code Changes
- Change `.env` → service switches automatically
- No code modifications needed
- Same interfaces, same methods

### 2. Same API Contracts
- Local services and HTTP clients implement the same interface
- Same DTOs, same method signatures
- Seamless transition

### 3. Easy Testing
- Test in monolith mode (fast, no HTTP)
- Test in microservice mode (with mocked HTTP)
- Same test code works for both

### 4. Gradual Migration
- Migrate one service at a time
- No big-bang deployment
- Easy rollback (just change config)

### 5. Rollback Capability
- Change config back to `monolith` to rollback
- No code changes needed
- Instant rollback

## Configuration Reference

### Environment Variables

```env
# Service Modes: 'monolith' or 'microservice'
AUTHENTICATION_SERVICE_MODE=monolith
AUTHENTICATION_SERVICE_URL=http://authentication-service.test
AUTHENTICATION_SERVICE_TIMEOUT=5

AUTHORIZATION_SERVICE_MODE=monolith
AUTHORIZATION_SERVICE_URL=http://authorization-service.test
AUTHORIZATION_SERVICE_TIMEOUT=5

TODO_SERVICE_MODE=monolith
TODO_SERVICE_URL=http://todo-service.test
TODO_SERVICE_TIMEOUT=5
```

### Service Provider Logic

```php
// AppServiceProvider automatically selects implementation

if (config('services.authentication.mode') === 'monolith') {
    // Local service (calls controllers directly)
    bind(LocalAuthenticationService::class);
} else {
    // HTTP client (makes HTTP requests)
    bind(AuthenticationServiceClient::class);
}
```

## Migration Checklist

### Pre-Migration
- [ ] All modules use service interfaces (no direct model access)
- [ ] API endpoints are implemented and tested
- [ ] Service contracts are defined
- [ ] DTOs are created and used

### Migration Steps
- [ ] Extract Authentication module to separate app
- [ ] Create separate database for Authentication
- [ ] Update Authentication service `.env`
- [ ] Test Authentication service independently
- [ ] Extract Authorization module to separate app
- [ ] Create separate database for Authorization
- [ ] Update Authorization service `.env` (point to Authentication)
- [ ] Test Authorization service
- [ ] Extract Todo module to separate app
- [ ] Create separate database for Todo
- [ ] Update Todo service `.env` (point to both services)
- [ ] Test all services together

### Post-Migration
- [ ] Update service URLs for production
- [ ] Configure service discovery (if needed)
- [ ] Set up monitoring and logging
- [ ] Configure API Gateway (if needed)
- [ ] Set up message queue (if needed)
- [ ] Update documentation

## Troubleshooting

### Service Not Found
**Problem:** `Class LocalAuthenticationService not found`

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
```

### HTTP Connection Failed
**Problem:** `Connection refused` when calling microservice

**Solution:**
1. Verify service is running
2. Check service URL in `.env`
3. Test with `curl` directly
4. Check firewall/network settings

### Cache Issues
**Problem:** Old data returned after migration

**Solution:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Database Connection Issues
**Problem:** Service can't connect to database

**Solution:**
1. Verify database exists
2. Check credentials in `.env`
3. Test connection: `php artisan tinker` → `DB::connection()->getPdo()`

## Next Steps After Migration

### 1. Service Discovery
Implement service discovery (Consul, Eureka, Kubernetes):
```php
// Update service clients to use discovery
$baseUrl = ServiceDiscovery::resolve('authentication-service');
```

### 2. API Gateway
Set up API Gateway (Kong, AWS API Gateway):
- Route external requests to services
- Handle authentication/authorization
- Rate limiting and throttling

### 3. Message Queue
Set up message queue (RabbitMQ, Redis, AWS SQS):
- Replace synchronous HTTP calls with async messages
- Implement event sourcing if needed

### 4. Monitoring
Set up monitoring:
- APM (New Relic, Datadog)
- Logging (ELK Stack, CloudWatch)
- Tracing (Jaeger, Zipkin)
- Metrics (Prometheus, Grafana)

## Summary

The migration to microservices with Option 1 is **configuration-driven**:

1. **Extract** modules to separate applications
2. **Configure** separate databases
3. **Update** `.env` files (change `SERVICE_MODE=microservice`)
4. **Deploy** services independently
5. **Done** - services automatically use HTTP clients

**No code changes needed** because:
- Service interfaces are the same
- DTOs are the same
- Method signatures are the same
- Only the implementation changes (local vs HTTP)

This makes the migration **straightforward, low-risk, and reversible**.

