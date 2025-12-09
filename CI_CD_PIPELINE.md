# CI/CD Pipeline Guide

## Overview

This guide provides comprehensive CI/CD pipeline configurations for the Laravel modular application with multi-tenancy, separate database connections, and Nwidart modules.

## Table of Contents

1. [GitHub Actions Workflow](#github-actions-workflow)
2. [GitLab CI/CD](#gitlab-cicd)
3. [Testing Strategy](#testing-strategy)
4. [Database Migration Strategy](#database-migration-strategy)
5. [Deployment Strategies](#deployment-strategies)
6. [Environment Configuration](#environment-configuration)
7. [Multi-Tenancy Considerations](#multi-tenancy-considerations)
8. [Module-Specific Considerations](#module-specific-considerations)

---

## GitHub Actions Workflow

### Complete Workflow File

**File:** `.github/workflows/ci.yml`

```yaml
name: CI/CD Pipeline

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

env:
  PHP_VERSION: '8.3'
  NODE_VERSION: '20'

jobs:
  lint:
    name: Code Linting
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: pdo, pdo_sqlite, mbstring, xml, ctype, json, tokenizer
          coverage: none

      - name: Cache Composer dependencies
        uses: actions/cache@v3
        with:
          path: vendor
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-composer-

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-progress --no-interaction

      - name: Run Laravel Pint
        run: vendor/bin/pint --test

  test:
    name: Run Tests
    runs-on: ubuntu-latest
    needs: lint
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    strategy:
      matrix:
        php-version: ['8.2', '8.3']
        test-suite: [unit, feature]

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: pdo, pdo_sqlite, pdo_mysql, mbstring, xml, ctype, json, tokenizer
          coverage: xdebug

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: ${{ env.NODE_VERSION }}

      - name: Cache Composer dependencies
        uses: actions/cache@v3
        with:
          path: vendor
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-composer-

      - name: Cache NPM dependencies
        uses: actions/cache@v3
        with:
          path: node_modules
          key: ${{ runner.os }}-npm-${{ hashFiles('**/package-lock.json') }}
          restore-keys: |
            ${{ runner.os }}-npm-

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-progress --no-interaction

      - name: Install NPM dependencies
        run: npm ci

      - name: Copy environment file
        run: cp .env.example .env.testing

      - name: Generate application key
        run: php artisan key:generate --env=testing

      - name: Setup test databases
        run: |
          mkdir -p Modules/Authentication/database
          mkdir -p Modules/Authorization/database
          mkdir -p Modules/Todo/database
          mkdir -p Modules/ConfigTransports/database
          mkdir -p database
          touch Modules/Authentication/database/authentication.sqlite
          touch Modules/Authorization/database/authorization.sqlite
          touch Modules/Todo/database/todo.sqlite
          touch Modules/ConfigTransports/database/config_transports.sqlite
          touch database/system.sqlite

      - name: Run migrations for all connections
        run: |
          php artisan migrate --database=system --path=database/migrations --force
          php artisan migrate --database=authentication --path=Modules/Authentication/database/migrations --force
          php artisan migrate --database=authorization --path=Modules/Authorization/database/migrations --force
          php artisan migrate --database=todo --path=Modules/Todo/database/migrations --force
          php artisan migrate --database=config_transports --path=Modules/ConfigTransports/database/migrations --force

      - name: Run tests
        env:
          DB_CONNECTION: sqlite
          AUTHENTICATION_DB_DRIVER: sqlite
          AUTHENTICATION_DB_DATABASE: Modules/Authentication/database/authentication.sqlite
          AUTHORIZATION_DB_DRIVER: sqlite
          AUTHORIZATION_DB_DATABASE: Modules/Authorization/database/authorization.sqlite
          TODO_DB_DRIVER: sqlite
          TODO_DB_DATABASE: Modules/Todo/database/todo.sqlite
          CONFIG_TRANSPORTS_DB_DRIVER: sqlite
          CONFIG_TRANSPORTS_DB_DATABASE: Modules/ConfigTransports/database/config_transports.sqlite
          SYSTEM_DB_DRIVER: sqlite
          SYSTEM_DB_DATABASE: database/system.sqlite
          APP_ENV: testing
          APP_DEBUG: false
        run: |
          if [ "${{ matrix.test-suite }}" == "unit" ]; then
            php artisan test --testsuite=Unit
          else
            php artisan test --testsuite=Feature
          fi

      - name: Upload coverage reports
        if: matrix.php-version == '8.3' && matrix.test-suite == 'feature'
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
          flags: unittests
          name: codecov-umbrella

  build:
    name: Build Assets
    runs-on: ubuntu-latest
    needs: test
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: ${{ env.NODE_VERSION }}

      - name: Cache NPM dependencies
        uses: actions/cache@v3
        with:
          path: node_modules
          key: ${{ runner.os }}-npm-${{ hashFiles('**/package-lock.json') }}
          restore-keys: |
            ${{ runner.os }}-npm-

      - name: Install NPM dependencies
        run: npm ci

      - name: Build assets
        run: npm run build

      - name: Upload build artifacts
        uses: actions/upload-artifact@v3
        with:
          name: build-assets
          path: |
            public/build
            public/hot

  deploy-staging:
    name: Deploy to Staging
    runs-on: ubuntu-latest
    needs: [test, build]
    if: github.ref == 'refs/heads/develop' && github.event_name == 'push'
    environment:
      name: staging
      url: https://staging.example.com

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup SSH
        uses: webfactory/ssh-agent@v0.7.0
        with:
          ssh-private-key: ${{ secrets.STAGING_SSH_PRIVATE_KEY }}

      - name: Deploy to staging server
        run: |
          ssh ${{ secrets.STAGING_SSH_USER }}@${{ secrets.STAGING_SSH_HOST }} << 'EOF'
            cd /var/www/staging
            git pull origin develop
            composer install --no-dev --optimize-autoloader
            npm ci
            npm run build
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
          EOF

  deploy-production:
    name: Deploy to Production
    runs-on: ubuntu-latest
    needs: [test, build]
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    environment:
      name: production
      url: https://example.com

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup SSH
        uses: webfactory/ssh-agent@v0.7.0
        with:
          ssh-private-key: ${{ secrets.PRODUCTION_SSH_PRIVATE_KEY }}

      - name: Deploy to production server
        run: |
          ssh ${{ secrets.PRODUCTION_SSH_USER }}@${{ secrets.PRODUCTION_SSH_HOST }} << 'EOF'
            cd /var/www/production
            git pull origin main
            composer install --no-dev --optimize-autoloader
            npm ci
            npm run build
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
          EOF
```

---

## GitLab CI/CD

### Complete GitLab CI Configuration

**File:** `.gitlab-ci.yml`

```yaml
stages:
  - lint
  - test
  - build
  - deploy

variables:
  PHP_VERSION: "8.3"
  NODE_VERSION: "20"
  MYSQL_ROOT_PASSWORD: "root"
  MYSQL_DATABASE: "testing"

cache:
  key: ${CI_COMMIT_REF_SLUG}
  paths:
    - vendor/
    - node_modules/

# Code Linting
lint:
  stage: lint
  image: php:${PHP_VERSION}-fpm
  before_script:
    - apt-get update -qq && apt-get install -y -qq git unzip libzip-dev libicu-dev libonig-dev
    - docker-php-ext-install pdo pdo_mysql zip intl mbstring
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install --prefer-dist --no-progress --no-interaction
  script:
    - vendor/bin/pint --test
  only:
    - merge_requests
    - main
    - develop

# Unit Tests
test:unit:
  stage: test
  image: php:${PHP_VERSION}-fpm
  services:
    - mysql:8.0
  variables:
    MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
    MYSQL_DATABASE: ${MYSQL_DATABASE}
    DB_HOST: mysql
    DB_CONNECTION: mysql
  before_script:
    - apt-get update -qq && apt-get install -y -qq git unzip libzip-dev libicu-dev libonig-dev
    - docker-php-ext-install pdo pdo_mysql zip intl mbstring
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install --prefer-dist --no-progress --no-interaction
    - cp .env.example .env.testing
    - php artisan key:generate --env=testing
    - |
      mkdir -p Modules/Authentication/database
      mkdir -p Modules/Authorization/database
      mkdir -p Modules/Todo/database
      mkdir -p Modules/ConfigTransports/database
      mkdir -p database
      touch Modules/Authentication/database/authentication.sqlite
      touch Modules/Authorization/database/authorization.sqlite
      touch Modules/Todo/database/todo.sqlite
      touch Modules/ConfigTransports/database/config_transports.sqlite
      touch database/system.sqlite
    - |
      php artisan migrate --database=system --path=database/migrations --force
      php artisan migrate --database=authentication --path=Modules/Authentication/database/migrations --force
      php artisan migrate --database=authorization --path=Modules/Authorization/database/migrations --force
      php artisan migrate --database=todo --path=Modules/Todo/database/migrations --force
      php artisan migrate --database=config_transports --path=Modules/ConfigTransports/database/migrations --force
  script:
    - php artisan test --testsuite=Unit
  coverage: '/^\s*Lines:\s*\d+.\d+\%/'
  artifacts:
    reports:
      junit: tests/results/unit.xml
      coverage_report:
        coverage_format: cobertura
        path: coverage/cobertura.xml

# Feature Tests
test:feature:
  stage: test
  image: php:${PHP_VERSION}-fpm
  services:
    - mysql:8.0
  variables:
    MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
    MYSQL_DATABASE: ${MYSQL_DATABASE}
    DB_HOST: mysql
    DB_CONNECTION: mysql
  before_script:
    - apt-get update -qq && apt-get install -y -qq git unzip libzip-dev libicu-dev libonig-dev
    - docker-php-ext-install pdo pdo_mysql zip intl mbstring
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install --prefer-dist --no-progress --no-interaction
    - cp .env.example .env.testing
    - php artisan key:generate --env=testing
    - |
      mkdir -p Modules/Authentication/database
      mkdir -p Modules/Authorization/database
      mkdir -p Modules/Todo/database
      mkdir -p Modules/ConfigTransports/database
      mkdir -p database
      touch Modules/Authentication/database/authentication.sqlite
      touch Modules/Authorization/database/authorization.sqlite
      touch Modules/Todo/database/todo.sqlite
      touch Modules/ConfigTransports/database/config_transports.sqlite
      touch database/system.sqlite
    - |
      php artisan migrate --database=system --path=database/migrations --force
      php artisan migrate --database=authentication --path=Modules/Authentication/database/migrations --force
      php artisan migrate --database=authorization --path=Modules/Authorization/database/migrations --force
      php artisan migrate --database=todo --path=Modules/Todo/database/migrations --force
      php artisan migrate --database=config_transports --path=Modules/ConfigTransports/database/migrations --force
  script:
    - php artisan test --testsuite=Feature
  coverage: '/^\s*Lines:\s*\d+.\d+\%/'
  artifacts:
    reports:
      junit: tests/results/feature.xml
      coverage_report:
        coverage_format: cobertura
        path: coverage/cobertura.xml

# Build Assets
build:
  stage: build
  image: node:${NODE_VERSION}
  script:
    - npm ci
    - npm run build
  artifacts:
    paths:
      - public/build/
      - public/hot
    expire_in: 1 week
  only:
    - main
    - develop

# Deploy to Staging
deploy:staging:
  stage: deploy
  image: alpine:latest
  before_script:
    - apk add --no-cache openssh-client rsync
    - eval $(ssh-agent -s)
    - echo "$STAGING_SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add -
    - mkdir -p ~/.ssh
    - chmod 700 ~/.ssh
    - ssh-keyscan $STAGING_SSH_HOST >> ~/.ssh/known_hosts
    - chmod 644 ~/.ssh/known_hosts
  script:
    - |
      ssh $STAGING_SSH_USER@$STAGING_SSH_HOST << 'EOF'
        cd /var/www/staging
        git pull origin develop
        composer install --no-dev --optimize-autoloader
        npm ci
        npm run build
        php artisan migrate --force
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan queue:restart
      EOF
  environment:
    name: staging
    url: https://staging.example.com
  only:
    - develop
  when: manual

# Deploy to Production
deploy:production:
  stage: deploy
  image: alpine:latest
  before_script:
    - apk add --no-cache openssh-client rsync
    - eval $(ssh-agent -s)
    - echo "$PRODUCTION_SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add -
    - mkdir -p ~/.ssh
    - chmod 700 ~/.ssh
    - ssh-keyscan $PRODUCTION_SSH_HOST >> ~/.ssh/known_hosts
    - chmod 644 ~/.ssh/known_hosts
  script:
    - |
      ssh $PRODUCTION_SSH_USER@$PRODUCTION_SSH_HOST << 'EOF'
        cd /var/www/production
        git pull origin main
        composer install --no-dev --optimize-autoloader
        npm ci
        npm run build
        php artisan migrate --force
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan queue:restart
      EOF
  environment:
    name: production
    url: https://example.com
  only:
    - main
  when: manual
```

---

## Testing Strategy

### Test Environment Setup

**File:** `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
            <directory>Modules/*/tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
            <directory>Modules/*/tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
            <directory>Modules</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="BROADCAST_CONNECTION" value="null"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="AUTHENTICATION_DB_DRIVER" value="sqlite"/>
        <env name="AUTHENTICATION_DB_DATABASE" value=":memory:"/>
        <env name="AUTHORIZATION_DB_DRIVER" value="sqlite"/>
        <env name="AUTHORIZATION_DB_DATABASE" value=":memory:"/>
        <env name="TODO_DB_DRIVER" value="sqlite"/>
        <env name="TODO_DB_DATABASE" value=":memory:"/>
        <env name="CONFIG_TRANSPORTS_DB_DRIVER" value="sqlite"/>
        <env name="CONFIG_TRANSPORTS_DB_DATABASE" value=":memory:"/>
        <env name="SYSTEM_DB_DRIVER" value="sqlite"/>
        <env name="SYSTEM_DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
        <env name="NIGHTWATCH_ENABLED" value="false"/>
    </php>
</phpunit>
```

### Test Script for CI

**File:** `scripts/test-ci.sh`

```bash
#!/bin/bash

set -e

echo "Setting up test databases..."

# Create database directories
mkdir -p Modules/Authentication/database
mkdir -p Modules/Authorization/database
mkdir -p Modules/Todo/database
mkdir -p Modules/ConfigTransports/database
mkdir -p database

# Create SQLite database files
touch Modules/Authentication/database/authentication.sqlite
touch Modules/Authorization/database/authorization.sqlite
touch Modules/Todo/database/todo.sqlite
touch Modules/ConfigTransports/database/config_transports.sqlite
touch database/system.sqlite

echo "Running migrations..."

# Run migrations for all connections
php artisan migrate --database=system --path=database/migrations --force
php artisan migrate --database=authentication --path=Modules/Authentication/database/migrations --force
php artisan migrate --database=authorization --path=Modules/Authorization/database/migrations --force
php artisan migrate --database=todo --path=Modules/Todo/database/migrations --force
php artisan migrate --database=config_transports --path=Modules/ConfigTransports/database/migrations --force

echo "Running tests..."
php artisan test
```

---

## Database Migration Strategy

### Multi-Database Migration Script

**File:** `scripts/migrate-all.sh`

```bash
#!/bin/bash

set -e

echo "Running migrations for all database connections..."

# System database (tenants)
php artisan migrate --database=system --path=database/migrations --force

# Module databases
php artisan migrate --database=authentication --path=Modules/Authentication/database/migrations --force
php artisan migrate --database=authorization --path=Modules/Authorization/database/migrations --force
php artisan migrate --database=todo --path=Modules/Todo/database/migrations --force
php artisan migrate --database=config_transports --path=Modules/ConfigTransports/database/migrations --force

echo "All migrations completed successfully!"
```

### Artisan Command for Migrations

**File:** `app/Console/Commands/MigrateAllCommand.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateAllCommand extends Command
{
    protected $signature = 'migrate:all {--fresh : Drop all tables and re-run migrations}';
    protected $description = 'Run migrations for all database connections';

    public function handle(): int
    {
        $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';
        $force = $this->getLaravel()->environment() === 'production' ? ['--force' => true] : [];

        $connections = [
            'system' => 'database/migrations',
            'authentication' => 'Modules/Authentication/database/migrations',
            'authorization' => 'Modules/Authorization/database/migrations',
            'todo' => 'Modules/Todo/database/migrations',
            'config_transports' => 'Modules/ConfigTransports/database/migrations',
        ];

        foreach ($connections as $connection => $path) {
            $this->info("Migrating {$connection} database...");
            $this->call($command, array_merge([
                '--database' => $connection,
                '--path' => $path,
            ], $force));
        }

        $this->info('All migrations completed!');
        return Command::SUCCESS;
    }
}
```

---

## Deployment Strategies

### Blue-Green Deployment

**File:** `scripts/deploy-blue-green.sh`

```bash
#!/bin/bash

set -e

DEPLOY_DIR="/var/www"
CURRENT_DIR="${DEPLOY_DIR}/current"
BLUE_DIR="${DEPLOY_DIR}/blue"
GREEN_DIR="${DEPLOY_DIR}/green"

# Determine current and target environments
if [ -L "$CURRENT_DIR" ] && [ "$(readlink $CURRENT_DIR)" == "$BLUE_DIR" ]; then
    TARGET_DIR="$GREEN_DIR"
    CURRENT_ENV="blue"
    TARGET_ENV="green"
else
    TARGET_DIR="$BLUE_DIR"
    CURRENT_ENV="green"
    TARGET_ENV="blue"
fi

echo "Deploying to $TARGET_ENV environment..."

# Deploy to target directory
cd $TARGET_DIR
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Run migrations
php artisan migrate:all --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Test the deployment
php artisan route:list > /dev/null

# Switch symlink
ln -sfn $TARGET_DIR $CURRENT_DIR

# Restart services
php artisan queue:restart
php artisan horizon:terminate 2>/dev/null || true

echo "Deployment to $TARGET_ENV completed successfully!"
```

### Zero-Downtime Deployment

**File:** `scripts/deploy-zero-downtime.sh`

```bash
#!/bin/bash

set -e

DEPLOY_DIR="/var/www"
RELEASE_DIR="${DEPLOY_DIR}/releases/$(date +%Y%m%d%H%M%S)"
CURRENT_DIR="${DEPLOY_DIR}/current"
SHARED_DIR="${DEPLOY_DIR}/shared"

echo "Creating release directory: $RELEASE_DIR"
mkdir -p $RELEASE_DIR

# Clone repository
git clone --depth 1 https://github.com/your-org/your-repo.git $RELEASE_DIR

cd $RELEASE_DIR

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Link shared directories
ln -sfn $SHARED_DIR/.env $RELEASE_DIR/.env
ln -sfn $SHARED_DIR/storage $RELEASE_DIR/storage
ln -sfn $SHARED_DIR/tenants $RELEASE_DIR/tenants

# Run migrations
php artisan migrate:all --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Test the release
php artisan route:list > /dev/null

# Switch symlink atomically
ln -sfn $RELEASE_DIR $CURRENT_DIR

# Restart services
php artisan queue:restart
php artisan horizon:terminate 2>/dev/null || true

# Cleanup old releases (keep last 5)
cd $DEPLOY_DIR/releases
ls -t | tail -n +6 | xargs rm -rf

echo "Deployment completed successfully!"
```

---

## Environment Configuration

### Environment-Specific Settings

**File:** `.env.example`

```env
# Application
APP_NAME="Laravel Modular App"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database Connections
DB_CONNECTION=sqlite

# Authentication Database
AUTHENTICATION_DB_DRIVER=sqlite
AUTHENTICATION_DB_DATABASE=Modules/Authentication/database/authentication.sqlite

# Authorization Database
AUTHORIZATION_DB_DRIVER=sqlite
AUTHORIZATION_DB_DATABASE=Modules/Authorization/database/authorization.sqlite

# Todo Database
TODO_DB_DRIVER=sqlite
TODO_DB_DATABASE=Modules/Todo/database/todo.sqlite

# Config Transports Database
CONFIG_TRANSPORTS_DB_DRIVER=sqlite
CONFIG_TRANSPORTS_DB_DATABASE=Modules/ConfigTransports/database/config_transports.sqlite

# System Database (Tenants)
SYSTEM_DB_DRIVER=sqlite
SYSTEM_DB_DATABASE=database/system.sqlite

# Multi-Tenancy
DEFAULT_TENANT_ID=1
TENANT_STRICT_ISOLATION=true

# System Configuration
SYSTEM_ENVIRONMENT_ROLE=dev
TRANSPORT_EXPORT_PATH=storage/app/transports

# Cache & Queue
CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Production Environment Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database connections
- [ ] Set up Redis for cache and queue
- [ ] Configure mail service
- [ ] Set up SSL certificates
- [ ] Configure backup strategy
- [ ] Set up monitoring and logging
- [ ] Configure CDN for assets
- [ ] Set up error tracking (Sentry, etc.)

---

## Multi-Tenancy Considerations

### Tenant-Aware Testing

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

    protected ?Tenant $tenant = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test tenant for all tests
        $this->tenant = Tenant::factory()->create();
        app(TenantService::class)->setCurrentTenant($this->tenant);
    }

    protected function tearDown(): void
    {
        // Clean up tenant databases
        if ($this->tenant) {
            $tenantService = app(TenantService::class);
            $tenantService->deleteTenant($this->tenant);
        }

        parent::tearDown();
    }
}
```

### Tenant Migration in CI

Add to CI workflow before running tests:

```yaml
- name: Create test tenant
  run: |
    php artisan migrate --database=system --path=database/migrations --force
    php artisan tenant:create "Test Tenant" --subdomain=test
    php artisan tenant:migrate 1
```

---

## Module-Specific Considerations

### Module Testing

Each module should have its own test suite:

```bash
# Run tests for specific module
php artisan test Modules/Authentication/tests
php artisan test Modules/Authorization/tests
php artisan test Modules/Todo/tests
php artisan test Modules/ConfigTransports/tests
```

### Module Deployment

Modules are deployed as part of the main application, but you can:

1. **Test modules independently** before integration
2. **Version modules** separately (using git tags)
3. **Deploy modules** incrementally if needed

### Module Cache Clearing

After deployment, clear module caches:

```bash
php artisan module:cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Monitoring and Alerts

### Health Check Endpoint

**File:** `routes/web.php`

```php
Route::get('/health', function () {
    $checks = [
        'database' => DB::connection()->getPdo() ? 'ok' : 'fail',
        'cache' => Cache::store()->getStore()->ping() ? 'ok' : 'fail',
        'queue' => 'ok', // Add queue health check
    ];

    $status = in_array('fail', $checks) ? 503 : 200;

    return response()->json([
        'status' => $status === 200 ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $status);
});
```

### CI/CD Notifications

Add to workflow:

```yaml
- name: Notify on failure
  if: failure()
  uses: 8398a7/action-slack@v3
  with:
    status: ${{ job.status }}
    text: 'CI/CD Pipeline failed!'
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

---

## Best Practices

### 1. Always Test Before Deploy

- Run full test suite
- Test on staging environment
- Manual smoke tests before production

### 2. Database Migrations

- Always backup before migrations
- Test migrations on staging first
- Use transactions where possible
- Have rollback plan ready

### 3. Environment Variables

- Never commit `.env` files
- Use secrets management
- Document all required variables
- Validate environment on startup

### 4. Deployment

- Use blue-green or zero-downtime deployment
- Monitor during and after deployment
- Have rollback procedure ready
- Deploy during low-traffic periods

### 5. Monitoring

- Set up application monitoring
- Monitor database connections
- Track error rates
- Monitor queue processing
- Set up alerts for critical issues

---

## Troubleshooting

### Common Issues

**Issue: Tests failing due to database connections**

**Solution:** Ensure all database connections are configured in test environment:

```php
// In phpunit.xml or .env.testing
AUTHENTICATION_DB_DRIVER=sqlite
AUTHENTICATION_DB_DATABASE=:memory:
```

**Issue: Migrations failing in CI**

**Solution:** Ensure migration paths are correct and databases exist:

```bash
mkdir -p Modules/*/database
touch Modules/*/database/*.sqlite
```

**Issue: Module not found after deployment**

**Solution:** Clear caches and regenerate autoload:

```bash
composer dump-autoload
php artisan module:cache:clear
php artisan config:clear
```

---

## Summary

This CI/CD pipeline guide provides:

1. **GitHub Actions** workflow for automated testing and deployment
2. **GitLab CI/CD** configuration as alternative
3. **Testing strategy** for multi-database, multi-tenant application
4. **Database migration** handling for all connections
5. **Deployment strategies** (blue-green, zero-downtime)
6. **Environment configuration** best practices
7. **Multi-tenancy** considerations in CI/CD
8. **Module-specific** deployment considerations

Follow these guidelines to ensure reliable, automated deployments of your Laravel modular application.

