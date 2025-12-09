# Database Migration Notes

## Important: How Migrations Work with Multiple Connections

When you have multiple database connections, Laravel's migration system works as follows:

### Running Migrations

**Option 1: Run all migrations (Recommended)**
```bash
php artisan migrate
```

This will:
- Run ALL migrations
- Each migration uses its own connection (via `Schema::connection('connection_name')`)
- The migrations table is tracked on the **default** connection
- Each migration creates tables in its **specified** connection

**Option 2: Run migrations for specific connection**
```bash
php artisan migrate --database=authentication
```

⚠️ **Warning**: When using `--database`, Laravel **overrides** the connection specified in migrations. All migrations will run on the specified connection, ignoring `Schema::connection()` calls.

### Migration Tracking

Laravel tracks which migrations have run in the `migrations` table on the **default** connection. However, each migration creates tables in its own specified connection.

### Best Practice

1. **Create all database files first** (for SQLite) or databases (for MySQL)
2. **Run migrations without `--database` flag**: `php artisan migrate`
3. Each migration will use its own connection automatically

### For Fresh Start

```bash
# Delete database files (SQLite)
Remove-Item Modules\Authentication\database\authentication.sqlite -ErrorAction SilentlyContinue
Remove-Item Modules\Authorization\database\authorization.sqlite -ErrorAction SilentlyContinue
Remove-Item Modules\Todo\database\todo.sqlite -ErrorAction SilentlyContinue

# Recreate database files
New-Item -ItemType File -Path "Modules\Authentication\database\authentication.sqlite" -Force | Out-Null
New-Item -ItemType File -Path "Modules\Authorization\database\authorization.sqlite" -Force | Out-Null
New-Item -ItemType File -Path "Modules\Todo\database\todo.sqlite" -Force | Out-Null

# Run migrations
php artisan migrate
```

### For MySQL

```sql
-- Create databases
CREATE DATABASE authentication_db;
CREATE DATABASE authorization_db;
CREATE DATABASE todo_db;
```

Then run:
```bash
php artisan migrate
```

Each migration will automatically use its configured connection.

## Troubleshooting

### Error: Database file does not exist

**Solution**: Create the database files first:
```bash
New-Item -ItemType File -Path "Modules\Authentication\database\authentication.sqlite" -Force | Out-Null
New-Item -ItemType File -Path "Modules\Authorization\database\authorization.sqlite" -Force | Out-Null
New-Item -ItemType File -Path "Modules\Todo\database\todo.sqlite" -Force | Out-Null
```

### Error: Table already exists

**Solution**: Drop tables from each connection:
```bash
php artisan migrate:fresh --database=authentication
php artisan migrate:fresh --database=authorization
php artisan migrate:fresh --database=todo
```

Or delete database files and recreate:
```bash
Remove-Item database\*.sqlite
New-Item -ItemType File -Path "database\authentication.sqlite", "database\authorization.sqlite", "database\todo.sqlite" -Force
php artisan migrate
```

### Migrations running on wrong connection

**Problem**: Using `--database` flag overrides migration connections.

**Solution**: Run migrations without `--database` flag:
```bash
php artisan migrate  # Not: php artisan migrate --database=authentication
```

