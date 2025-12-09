# SAP-style Config Transport System Documentation

## Overview

The Config Transport System is a generic, SAP-style Change and Transport System (CTS) for managing configuration and customizing data between different environments (DEV, QA, PROD). It allows you to bundle changes to selected domain objects into "transport requests", export them as JSON files, and import them into other environments.

## Key Concepts

### Transport Request
A container that groups related configuration changes together. Each transport request has:
- **Number**: Auto-generated sequential number (e.g., `DEVK900001`, `DEVK900002`)
- **Type**: Category of changes (security, config, master_data, mixed)
- **Status**: Current state (open, released, exported, imported, failed)
- **Source/Target Environments**: Where the transport originated and where it should be applied

### Transport Item
Individual change records within a transport request. Each item represents:
- **Object Type**: The type of object being changed (e.g., `role`, `auth_object`)
- **Identifier**: Natural key(s) that uniquely identify the object
- **Operation**: The type of change (create, update, delete)
- **Payload**: The data to be applied

### Transportable Models
Models that implement the `Transportable` interface can be automatically tracked and transported. The system currently supports:
- `AuthObject`
- `AuthObjectField`
- `Role`
- `RoleAuthorization`
- `RoleAuthorizationField`

## How It Works

### 1. Development Environment (DEV)

In the DEV environment, the system automatically records all changes to transportable models:

1. **Automatic Recording**: When you create, update, or delete a transportable model, the `TransportableObserver` automatically creates a `TransportItem` in the current open `TransportRequest`.

2. **Transport Request Creation**: If no open transport request exists, one is automatically created with the next sequential number.

3. **Transport Request Management**: 
   - View all transport requests at `/admin/transports`
   - Create new transport requests manually
   - View items in each transport request
   - Release transport requests when ready

4. **Export**: Once a transport request is released, you can export it to a JSON file using:
   ```bash
   php artisan transports:export DEVK900001
   ```
   This creates a file at `storage/app/transports/DEVK900001.json`

### 2. Quality Assurance / Production Environments (QA/PROD)

In QA and PROD environments:

1. **Edit Protection**: Direct edits to transportable models are blocked by the `TransportEditProtection` middleware. You'll receive a 403 error if you try to edit directly.

2. **Import Only**: Changes must come through transport imports:
   ```bash
   php artisan transports:import storage/app/transports/DEVK900001.json
   ```

3. **Dependency Resolution**: The import command automatically resolves dependencies and imports objects in the correct order.

4. **Import Logging**: Each import creates a `TransportImportLog` entry with status and summary information.

## Workflow Example

### Scenario: Creating a New Role

#### Step 1: Development (DEV)
1. Navigate to `/admin/authorization/roles/create`
2. Create a new role "SalesManager" with description "Manages sales operations"
3. The system automatically:
   - Creates a `TransportItem` with operation `create`
   - Associates it with the current open `TransportRequest` (or creates a new one)

#### Step 2: Review Transport Request
1. Navigate to `/admin/transports`
2. Click on the open transport request
3. Review the items - you should see the "SalesManager" role creation
4. Add a description to the transport request if needed

#### Step 3: Release Transport Request
1. Click "Release Transport Request" button
2. The status changes from `open` to `released`
3. The transport request is now ready for export

#### Step 4: Export Transport
```bash
php artisan transports:export DEVK900001
```
This creates `storage/app/transports/DEVK900001.json` containing:
- Transport metadata (number, type, description, etc.)
- All items with their final state (multiple updates to the same object are collapsed)

#### Step 5: Import to QA
1. Copy the JSON file to the QA environment
2. Run the import command:
   ```bash
   php artisan transports:import storage/app/transports/DEVK900001.json
   ```
3. The system:
   - Validates the transport file
   - Resolves dependencies
   - Applies each item in order
   - Creates an import log entry

#### Step 6: Verify
1. Check the import log at `/admin/transports` (if UI is extended)
2. Verify the role exists in QA: `/admin/authorization/roles`
3. The role "SalesManager" should now be available in QA

## Configuration

### Environment Role

The system determines the environment role from `APP_ENV` or `SYSTEM_ENVIRONMENT_ROLE`:

```php
// config/system.php
'environment_role' => env('SYSTEM_ENVIRONMENT_ROLE', function () {
    $appEnv = env('APP_ENV', 'production');
    return match ($appEnv) {
        'local', 'development' => 'dev',
        'staging', 'testing' => 'qa',
        'production' => 'prod',
        default => 'dev',
    };
}),
```

### Edit Protection

Edit protection is automatically enabled in QA/PROD:

```php
'transport_edit_protection' => env('SYSTEM_TRANSPORT_EDIT_PROTECTION', function () {
    $role = config('system.environment_role', 'dev');
    return $role !== 'dev';
}),
```

### Conflict Resolution

Default conflict resolution strategy:

```php
'default_conflict_resolution' => env('SYSTEM_DEFAULT_CONFLICT_RESOLUTION', 'update'),
```

Options:
- `update`: Update existing objects (idempotent)
- `skip`: Skip if object already exists
- `fail`: Fail the import if conflict detected

## Making Models Transportable

To make a new model transportable:

### 1. Implement the Interface

```php
use Modules\ConfigTransports\Contracts\Transportable;
use Modules\ConfigTransports\Concerns\IsTransportable;

class MyModel extends Model implements Transportable
{
    use IsTransportable;
    
    // ... your model code
}
```

### 2. Override Required Methods

```php
/**
 * Get the transport object type code.
 */
public static function getTransportObjectType(): string
{
    return 'my_model';
}

/**
 * Get the transport identifier (natural key).
 */
public function getTransportIdentifier(): array|string
{
    return $this->code; // or ['key1' => $this->key1, 'key2' => $this->key2]
}

/**
 * Get the transport payload (exclude IDs, timestamps).
 */
public function toTransportPayload(): array
{
    return [
        'code' => $this->code,
        'name' => $this->name,
        // ... other fields
    ];
}

/**
 * Apply transport payload (idempotent).
 */
public static function applyTransportPayload(array|string $identifier, array $payload, string $operation): void
{
    $code = is_array($identifier) ? ($identifier['code'] ?? $identifier['key'] ?? null) : $identifier;
    
    match ($operation) {
        'create', 'update' => static::updateOrCreate(
            ['code' => $code],
            $payload
        ),
        'delete' => static::where('code', $code)->delete(),
        default => throw new \InvalidArgumentException("Unknown operation: {$operation}"),
    };
}

/**
 * Get transport dependencies.
 */
public function getTransportDependencies(): array
{
    return [
        ['type' => 'other_model', 'identifier' => $this->other_model_code],
    ];
}
```

### 3. Register Observer

In `ConfigTransportsServiceProvider::registerObservers()`:

```php
\App\Models\MyModel::observe($observer);
```

## Permissions

The system uses two permission levels:

### Transport Admin
- Can create and release transport requests
- Gate: `transport_admin`
- Requires: `TRANSPORT_MGMT` AuthObject with `ADMIN` field

### Transport Operator
- Can view, export, and import transport requests
- Gate: `transport_operator`
- Requires: `TRANSPORT_MGMT` AuthObject with `OPERATOR` or `ADMIN` field

### Setup Permissions

Run the seeder to create the AuthObject:

```bash
php artisan db:seed --class=TransportManagementAuthObjectSeeder
```

Then assign permissions to roles via the authorization UI.

## Command Reference

### Export Transport

```bash
php artisan transports:export {number} [--path=custom/path.json]
```

**Parameters:**
- `number`: Transport request number (e.g., `DEVK900001`)
- `--path`: Optional custom output path

**Requirements:**
- Transport request must be in `released` status

**Output:**
- JSON file at `storage/app/transports/{number}.json`
- Updates transport request status to `exported`

### Import Transport

```bash
php artisan transports:import {path} [--force]
```

**Parameters:**
- `path`: Path to the transport JSON file
- `--force`: Force import in DEV environment (normally blocked)

**Requirements:**
- Must be run in QA/PROD environment (or use `--force`)
- Valid JSON transport file

**Output:**
- Creates/updates/deletes objects as specified
- Creates `TransportImportLog` entry
- Returns success/partial/failed status

## JSON Transport File Format

```json
{
  "transport": {
    "number": "DEVK900001",
    "type": "security",
    "description": "Add new sales role",
    "source_environment": "dev",
    "target_environments": ["qa", "prod"],
    "created_by": 1,
    "released_by": 1,
    "released_at": "2025-11-28T10:00:00Z"
  },
  "items": [
    {
      "object_type": "role",
      "identifier": {"key": "SalesManager"},
      "operation": "create",
      "payload": {
        "name": "SalesManager",
        "description": "Manages sales operations"
      },
      "meta": {
        "recorded_at": "2025-11-28T09:00:00Z",
        "recorded_by": 1
      }
    }
  ]
}
```

## Dependency Resolution

The import command automatically resolves dependencies:

1. **Dependency Graph**: Builds a graph from `getTransportDependencies()` methods
2. **Topological Sort**: Orders items so dependencies are imported first
3. **Validation**: Ensures all dependencies exist before import
4. **Error Handling**: Detects and reports circular dependencies

Example:
- `RoleAuthorization` depends on `Role` and `AuthObject`
- `RoleAuthorizationField` depends on `RoleAuthorization`
- Import order: `Role` → `AuthObject` → `RoleAuthorization` → `RoleAuthorizationField`

## Item Collapsing

When exporting, multiple items for the same object are collapsed into a single final state:

- Multiple `create`/`update` operations → Final `update` with merged payload
- `delete` followed by `create` → Final `create`
- `create` followed by `delete` → Final `delete`

This ensures the exported transport represents the final state, not the history.

## Environment Protection

### DEV Environment
- ✅ Direct edits allowed
- ✅ Automatic recording enabled
- ✅ Transport requests can be created/released
- ❌ Imports blocked (use `--force` to override)

### QA Environment
- ❌ Direct edits blocked (403 error)
- ❌ Automatic recording disabled
- ✅ Imports allowed
- ✅ View-only access to transportable models

### PROD Environment
- ❌ Direct edits blocked (403 error)
- ❌ Automatic recording disabled
- ✅ Imports allowed
- ✅ View-only access to transportable models

## Troubleshooting

### Transport Not Recording Changes

**Check:**
1. Environment role is set to `dev`: `config('system.environment_role')`
2. User is authenticated
3. Model implements `Transportable` interface
4. Observer is registered in `ConfigTransportsServiceProvider`

### Import Fails with "Handler not found"

**Solution:**
- Ensure the model class is properly registered
- Check that the `object_type` in the JSON matches `getTransportObjectType()`
- Verify the model implements `Transportable` interface

### Circular Dependency Error

**Solution:**
- Review `getTransportDependencies()` methods
- Ensure dependencies form a valid DAG (Directed Acyclic Graph)
- Consider restructuring dependencies if circular

### Edit Blocked in DEV

**Check:**
- `SYSTEM_ENVIRONMENT_ROLE` environment variable
- `config/system.php` environment_role setting
- Middleware is not incorrectly applied

## Best Practices

1. **Descriptive Transport Requests**: Always add descriptions to transport requests
2. **Review Before Release**: Review all items before releasing a transport request
3. **Test in QA First**: Always import to QA before PROD
4. **Use Natural Keys**: Use stable identifiers (codes, names) instead of IDs
5. **Idempotent Operations**: Ensure `applyTransportPayload()` can be run multiple times safely
6. **Dependency Management**: Keep dependencies minimal and clear
7. **Version Control**: Commit transport JSON files to version control for audit trail

## API Reference

### TransportRequest Model

```php
// Scopes
TransportRequest::open()->get();
TransportRequest::released()->get();
TransportRequest::forType('security')->get();

// Methods
$request->canBeReleased(); // bool
$request->canBeExported(); // bool
$request->release($userId); // void

// Relationships
$request->items; // HasMany TransportItem
$request->creator; // BelongsTo User
$request->releaser; // BelongsTo User
```

### TransportRecorder Service

```php
$recorder = app(TransportRecorder::class);

$recorder->recordCreate($model);
$recorder->recordUpdate($model);
$recorder->recordDelete($model);
$recorder->getActiveRequest($model); // TransportRequest|null
```

## Extending the System

### Adding New Transport Types

1. Add new type to `transport_requests.type` enum in migration
2. Update `TransportRecorder::getTransportTypeForModel()` mapping
3. Update UI dropdowns if needed

### Custom Conflict Resolution

Override in `ImportTransportCommand::getConflictStrategy()`:

```php
protected function getConflictStrategy(string $objectType): string
{
    return match ($objectType) {
        'role' => 'update',
        'auth_object' => 'skip',
        default => config('system.default_conflict_resolution', 'update'),
    };
}
```

### Custom Export Path

Use the `--path` option:

```bash
php artisan transports:export DEVK900001 --path=custom/exports/my-transport.json
```

## Security Considerations

1. **Permission Checks**: All transport operations require proper permissions
2. **Environment Isolation**: QA/PROD environments block direct edits
3. **Audit Trail**: All operations are logged with user and timestamp
4. **Validation**: Transport files are validated before import
5. **Transaction Safety**: Imports are wrapped in database transactions

## Future Enhancements

Potential improvements:
- Transport request approval workflow
- Rollback functionality
- Transport comparison/diff
- Scheduled imports
- Email notifications
- Transport request templates
- Bulk operations
- Transport request merging
- Advanced dependency visualization

