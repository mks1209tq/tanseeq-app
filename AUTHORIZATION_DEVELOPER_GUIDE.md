# Authorization Developer Guide

## Overview

This application enforces **mandatory authorization checks** on all user-facing routes. Every route that requires authentication **MUST** also include an authorization check using the `auth.object` middleware. This ensures that all user actions are explicitly authorized and prevents unauthorized access.

## Core Principle

**Every authenticated route MUST have an authorization check.**

The only exceptions are:
- Guest routes (login, password reset, etc.)
- Routes that are part of the authentication flow itself (email verification, password confirmation)
- **SuperAdmin users** - SuperAdmin completely bypasses the entire authorization system (no checks, no database queries, no logging)

## Authorization System Architecture

### Authorization Objects

Authorization objects represent features or modules that require access control. Each authorization object has:
- A unique code (e.g., `DASHBOARD_ACCESS`, `USER_MANAGEMENT`)
- A description
- Fields (typically `ACTVT` for activity type)

### Activity Codes (ACTVT)

Standard SAP-style activity codes:
- `01` - Create
- `02` - Change/Update
- `03` - Display/View
- `06` - Delete
- `07` - Lock
- `08` - Unlock
- `09` - Post
- `10` - Cancel
- `11` - Print
- `12` - Export
- `13` - Import

### Role Authorizations

Roles are assigned authorizations for specific objects with field-level rules. For example:
- SuperAdmin role has `*` (wildcard) access to all objects
- Other roles have specific field restrictions

## Available Authorization Objects

| Code | Description |
|------|-------------|
| `DASHBOARD_ACCESS` | Main application dashboard |
| `TENANT_MANAGEMENT` | Tenant CRUD operations |
| `USER_MANAGEMENT` | User creation and management |
| `PROFILE_MANAGEMENT` | User profile editing |
| `AUTHORIZATION_MODULE` | Authorization administration |
| `AUTHENTICATION_SETTINGS` | Authentication settings management |
| `AUTHORIZATION_DEBUG` | Authorization debug tools |
| `TRANSPORT_MANAGEMENT` | Transport request management (CTS) |
| `SALES_ORDER_HEADER` | Sales order operations |
| `FIN_DOCUMENT` | Financial document operations |
| `CUSTOMER_MASTER` | Customer master data operations |
| `TODO_MANAGEMENT` | Todo management operations |
| `UI_MANAGEMENT` | UI resource management operations |

## How to Add Authorization to Routes

### Basic Route Protection

```php
Route::middleware(['auth', 'auth.object:DASHBOARD_ACCESS'])->get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');
```

### Route Groups

```php
Route::middleware(['auth', 'auth.object:USER_MANAGEMENT'])->prefix('admin')->name('admin.users.')->group(function () {
    Route::get('users/create', [UserController::class, 'create'])->name('create');
    Route::post('users', [UserController::class, 'store'])->name('store');
});
```

### With Activity Code

If you need to specify a specific activity:

```php
Route::middleware(['auth', 'auth.object:SALES_ORDER_HEADER,03'])->get('/orders', function () {
    // Only allows Display (03) activity
})->name('orders.index');
```

### Multiple Authorization Objects

For routes that require multiple authorizations, check them in the controller:

```php
public function index()
{
    $this->authorize('auth-object', 'DASHBOARD_ACCESS');
    $this->authorize('auth-object', 'USER_MANAGEMENT');
    
    // Your code here
}
```

## Creating New Authorization Objects

### Step 1: Add to Seeder

Add the authorization object to `Modules/Authorization/database/seeders/AuthorizationDatabaseSeeder.php`:

```php
$newAuthObject = AuthObject::firstOrCreate(
    ['code' => 'NEW_FEATURE'],
    ['description' => 'New Feature - Controls access to the new feature']
);

AuthObjectField::firstOrCreate(
    [
        'auth_object_id' => $newAuthObject->id,
        'code' => 'ACTVT',
    ],
    [
        'label' => 'Activity',
        'is_org_level' => false,
        'sort' => 1,
    ]
);
```

### Step 2: Grant SuperAdmin Access (Optional)

**Note:** SuperAdmin completely bypasses the authorization system, so these authorizations are optional. They are created for consistency and potential future use, but SuperAdmin doesn't actually need them.

```php
// Optional: Create SuperAdmin authorization (not required since SuperAdmin bypasses system)
$superAdminNewFeatureAuth = RoleAuthorization::firstOrCreate(
    [
        'role_id' => $superAdminRole->id,
        'auth_object_id' => $newAuthObject->id,
    ],
    [
        'label' => 'SuperAdmin - Full Access to New Feature',
    ]
);

RoleAuthorizationField::firstOrCreate(
    [
        'role_authorization_id' => $superAdminNewFeatureAuth->id,
        'field_code' => 'ACTVT',
    ],
    [
        'operator' => '*',
    ]
);
```

### Step 3: Run Seeder

```bash
php artisan db:seed --class=Modules\\Authorization\\Database\\Seeders\\AuthorizationDatabaseSeeder
```

### Step 4: Use in Routes

```php
Route::middleware(['auth', 'auth.object:NEW_FEATURE'])->group(function () {
    // Your routes
});
```

## Developer Checklist

When creating new routes, ensure:

- [ ] Route has `auth` middleware (if it requires authentication)
- [ ] Route has `auth.object:OBJECT_CODE` middleware
- [ ] Authorization object exists in the seeder
- [ ] SuperAdmin role has access to the new object
- [ ] Tests verify authorization enforcement
- [ ] Documentation updated with new authorization object

## Testing Authorization

### Feature Test Example

```php
it('denies access without proper authorization', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertForbidden();
});

it('allows access with proper authorization', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'DashboardUser']);
    $user->roles()->attach($role);

    // Create authorization for the role
    $authObject = AuthObject::where('code', 'DASHBOARD_ACCESS')->first();
    $roleAuth = RoleAuthorization::factory()->create([
        'role_id' => $role->id,
        'auth_object_id' => $authObject->id,
    ]);

    RoleAuthorizationField::factory()->create([
        'role_authorization_id' => $roleAuth->id,
        'field_code' => 'ACTVT',
        'operator' => '*',
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertSuccessful();
});
```

## Common Patterns

### CRUD Operations

For full CRUD operations, use wildcard:

```php
Route::middleware(['auth', 'auth.object:USER_MANAGEMENT'])->group(function () {
    Route::resource('users', UserController::class);
});
```

### Read-Only Access

For read-only access, specify activity code:

```php
Route::middleware(['auth', 'auth.object:REPORTS,03'])->get('/reports', [ReportController::class, 'index']);
```

### Conditional Authorization

In controllers, check authorization conditionally:

```php
public function edit(User $user)
{
    // Users can edit their own profile, admins can edit any profile
    if ($user->id !== auth()->id()) {
        $this->authorize('auth-object', 'USER_MANAGEMENT');
    } else {
        $this->authorize('auth-object', 'PROFILE_MANAGEMENT');
    }
    
    // Your code
}
```

## Route-to-Authorization Mapping

| Route Pattern | Authorization Object | Notes |
|--------------|---------------------|-------|
| `/dashboard` | `DASHBOARD_ACCESS` | Main dashboard |
| `/admin/tenants/*` | `TENANT_MANAGEMENT` | All tenant operations |
| `/admin/users/*` | `USER_MANAGEMENT` | User creation |
| `/profile` | `PROFILE_MANAGEMENT` | Own profile editing |
| `/admin/authentication/settings` | `AUTHENTICATION_SETTINGS` | Auth settings |
| `/admin/authorization/*` | `AUTHORIZATION_MODULE` | Auth module admin |
| `/authorization-debug/*` | `AUTHORIZATION_DEBUG` | Debug tools |
| `/admin/transports/*` | `TRANSPORT_MANAGEMENT` | Transport requests |
| `/authentication/dashboard` | `DASHBOARD_ACCESS` | Auth module dashboard |
| `/authorization/dashboard` | `AUTHORIZATION_MODULE` | Auth module dashboard |
| `/todos/*` | `TODO_MANAGEMENT` | Todo CRUD operations |
| `/uis/*` | `UI_MANAGEMENT` | UI resource operations |

## SuperAdmin Bypass

**SuperAdmin users completely bypass the authorization system.**

- No authorization checks are performed
- No database queries are made
- No authorization logs are created
- Immediate access is granted for all operations

This means:
- SuperAdmin does not need authorization objects created for them
- SuperAdmin does not need role authorizations
- The authorization system is completely skipped for SuperAdmin users

**Note:** While SuperAdmin bypasses authorization, you should still:
- Add `auth.object` middleware to all routes (SuperAdmin will bypass it automatically)
- Create authorization objects for proper access control for other users
- Grant appropriate roles access to authorization objects

## SuperReadOnly Bypass

**SuperReadOnly users bypass authorization for read-only operations.**

- Bypasses authorization for display/view operations (ACTVT = '03')
- Bypasses authorization when no activity code is specified (defaults to read)
- Still requires authorization for create, change, delete, and other write operations
- No database queries for read-only checks
- Immediate access granted for read operations

This means:
- SuperReadOnly can view/display all data without authorization
- SuperReadOnly still needs proper authorization for write operations
- Useful for auditors, support staff, or read-only administrators

**Supported role names:**
- `SuperReadOnly`
- `super-read-only`
- `SUPER_READ_ONLY`
- `READ_ONLY_ADMIN`

## Best Practices

1. **Always use authorization objects** - Never skip authorization checks (SuperAdmin will bypass automatically)
2. **Use descriptive object codes** - Make it clear what the object controls
3. **SuperAdmin bypasses automatically** - No need to grant SuperAdmin access (they bypass the system)
4. **Test authorization** - Write tests that verify both allowed and denied cases
5. **Document new objects** - Update this guide when adding new authorization objects
6. **Use consistent naming** - Follow the `FEATURE_ACTION` or `MODULE_ACCESS` pattern
7. **Consider field-level restrictions** - Use field rules for fine-grained control

## Troubleshooting

### 403 Forbidden Errors

If users get 403 errors:
1. Check if the route has `auth.object` middleware
2. Verify the authorization object exists
3. Check if the user's role has authorization for the object
4. Verify the authorization field rules match the required fields

### Authorization Not Working

1. Clear cache: `php artisan cache:clear`
2. Verify authorization object exists in database
3. Check role authorizations are correctly set up
4. Review authorization debug page (`/auth/su53`)

## Code Review Checklist

When reviewing code that adds new routes:

- [ ] All authenticated routes have `auth.object` middleware
- [ ] Authorization object is created in seeder
- [ ] Tests verify authorization enforcement
- [ ] Documentation updated
- [ ] No hardcoded authorization bypasses (SuperAdmin bypasses automatically)

## Enforcement

Automated checks are in place to ensure:
- All routes are checked for authorization middleware
- Tests verify authorization enforcement
- CI/CD pipeline validates authorization requirements

See `tests/Feature/RouteAuthorizationTest.php` for automated route authorization checks.

