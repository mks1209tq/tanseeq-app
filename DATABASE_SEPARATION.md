# Database Separation Guide

## Overview

The application now uses **separate database connections** for each module/service. This allows for true database separation when migrating to microservices, while still working in monolith mode.

## Database Connections

### Authentication Database
- **Connection Name**: `authentication`
- **Tables**: `users`, `password_reset_tokens`, `sessions`, `auth_settings`
- **Models**: `User`, `AuthSetting`

### Authorization Database
- **Connection Name**: `authorization`
- **Tables**: `roles`, `role_user`, `auth_objects`, `auth_object_fields`, `role_authorizations`, `role_authorization_fields`
- **Models**: `Role`, `AuthObject`, `AuthObjectField`, `RoleAuthorization`, `RoleAuthorizationField`

### Todo Database
- **Connection Name**: `todo`
- **Tables**: `todos`
- **Models**: `Todo`

## Configuration

### Environment Variables

Add these to your `.env` file:

#### For SQLite (Default - Separate Files)

Database files are located in their respective modules:
- `Modules/Authentication/database/authentication.sqlite`
- `Modules/Authorization/database/authorization.sqlite`
- `Modules/Todo/database/todo.sqlite`

You can override these paths in `.env` if needed:

```env
# Authentication Database
AUTHENTICATION_DB_DRIVER=sqlite
AUTHENTICATION_DB_DATABASE=Modules/Authentication/database/authentication.sqlite

# Authorization Database
AUTHORIZATION_DB_DRIVER=sqlite
AUTHORIZATION_DB_DATABASE=Modules/Authorization/database/authorization.sqlite

# Todo Database
TODO_DB_DRIVER=sqlite
TODO_DB_DATABASE=Modules/Todo/database/todo.sqlite
```

#### For MySQL (Separate Databases)

```env
# Authentication Database
AUTHENTICATION_DB_DRIVER=mysql
AUTHENTICATION_DB_HOST=127.0.0.1
AUTHENTICATION_DB_PORT=3306
AUTHENTICATION_DB_DATABASE=authentication_db
AUTHENTICATION_DB_USERNAME=root
AUTHENTICATION_DB_PASSWORD=

# Authorization Database
AUTHORIZATION_DB_DRIVER=mysql
AUTHORIZATION_DB_HOST=127.0.0.1
AUTHORIZATION_DB_PORT=3306
AUTHORIZATION_DB_DATABASE=authorization_db
AUTHORIZATION_DB_USERNAME=root
AUTHORIZATION_DB_PASSWORD=

# Todo Database
TODO_DB_DRIVER=mysql
TODO_DB_HOST=127.0.0.1
TODO_DB_PORT=3306
TODO_DB_DATABASE=todo_db
TODO_DB_USERNAME=root
TODO_DB_PASSWORD=
```

## Setting Up Databases

### SQLite Setup

The SQLite database files will be created automatically when you run migrations. No manual setup needed.

### MySQL Setup

1. **Create Databases:**

```sql
CREATE DATABASE authentication_db;
CREATE DATABASE authorization_db;
CREATE DATABASE todo_db;
```

2. **Grant Permissions (if needed):**

```sql
GRANT ALL PRIVILEGES ON authentication_db.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON authorization_db.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON todo_db.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

## Running Migrations

### Run All Migrations

```bash
# Run migrations for each connection
php artisan migrate --database=authentication
php artisan migrate --database=authorization
php artisan migrate --database=todo
```

### Rollback Migrations

```bash
# Rollback each connection separately
php artisan migrate:rollback --database=authentication
php artisan migrate:rollback --database=authorization
php artisan migrate:rollback --database=todo
```

### Fresh Migrations (Reset All)

```bash
php artisan migrate:fresh --database=authentication
php artisan migrate:fresh --database=authorization
php artisan migrate:fresh --database=todo
```

## Important Notes

### Cross-Database References

**Foreign keys have been removed** for cross-database references:

1. **`role_user.user_id`** - No foreign key to `users` table
   - `user_id` is now just an integer reference
   - Validation happens at application level via `AuthenticationServiceInterface`

2. **`todos.user_id`** - No foreign key to `users` table
   - `user_id` is now just an integer reference
   - User data is fetched via `AuthenticationServiceInterface`

### Why Remove Foreign Keys?

- **Database separation**: Foreign keys cannot span different databases
- **Microservice readiness**: Each service will have its own database
- **Service boundaries**: Data integrity is maintained at the application/service layer

### Data Integrity

Even without foreign keys, data integrity is maintained through:

1. **Service Interfaces**: All cross-service data access goes through service contracts
2. **Application Validation**: Validation rules ensure valid user IDs
3. **Event-Driven Sync**: Events ensure data consistency across services

## Model Usage

All models automatically use their designated connection:

```php
// User model uses 'authentication' connection
$user = User::find(1);

// Role model uses 'authorization' connection
$role = Role::find(1);

// Todo model uses 'todo' connection
$todo = Todo::find(1);
```

## Testing

When running tests, you may want to use the same database for all connections:

```env
# In phpunit.xml or .env.testing
AUTHENTICATION_DB_DATABASE=database/testing.sqlite
AUTHORIZATION_DB_DATABASE=database/testing.sqlite
TODO_DB_DATABASE=database/testing.sqlite
```

Or use separate test databases:

```env
AUTHENTICATION_DB_DATABASE=database/testing_authentication.sqlite
AUTHORIZATION_DB_DATABASE=database/testing_authorization.sqlite
TODO_DB_DATABASE=database/testing_todo.sqlite
```

## Migration to Microservices

When you extract modules to separate services:

1. **Each service uses its own database connection**
2. **No code changes needed** - connections are already separated
3. **Just update `.env` files** in each service to point to their databases
4. **Cross-database references** are already handled via service interfaces

## Troubleshooting

### Connection Errors

If you get connection errors:

1. **Check `.env` file** - Ensure all database variables are set
2. **Verify database exists** - For MySQL, ensure databases are created
3. **Check permissions** - Ensure database user has proper permissions
4. **Clear config cache**: `php artisan config:clear`

### Migration Errors

If migrations fail:

1. **Check connection name** - Ensure migrations use correct connection
2. **Verify database exists** - For MySQL, create databases first
3. **Check foreign key constraints** - Ensure no cross-database FKs remain
4. **Run migrations separately** - Use `--database` flag for each connection

### Model Connection Errors

If models can't find their connection:

1. **Check model** - Ensure `protected $connection` is set
2. **Verify config** - Check `config/database.php` has the connection
3. **Clear cache**: `php artisan config:clear`

## Benefits

✅ **True Database Separation** - Each module has its own database  
✅ **Microservice Ready** - Easy to extract to separate services  
✅ **No Code Changes** - Models automatically use correct connections  
✅ **Flexible** - Can use same or different databases  
✅ **Scalable** - Each database can scale independently  

