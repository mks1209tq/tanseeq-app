<!-- b07905cc-8f05-4ae4-b9e1-97906d76aa1a c7d97e36-1f45-4d14-9fb2-277b5a732bee -->
# SU53-like Authorization Debug Feature Implementation

## Overview

Implement a SU53-like "last failed authorization check" feature by creating a new `AuthorizationDebug` module that logs authorization checks (both successes and failures), stores detailed trace information, and provides a debug UI for viewing the last failure for a user.

## Module Structure: `Modules/AuthorizationDebug/`

### 1. Create the AuthorizationDebug Module

- Run `php artisan module:make AuthorizationDebug`
- This creates the base module structure at `Modules/AuthorizationDebug/`

### 2. Database Migration: `authorization_failures` Table

**File**: `Modules/AuthorizationDebug/database/migrations/YYYY_MM_DD_HHMMSS_create_authorization_failures_table.php`

- Fields:
- `id` (bigint, PK)
- `user_id` (unsignedBigInteger, nullable - for guest failures)
- `auth_object_code` (string, NOT NULL)
- `required_fields` (JSON, NOT NULL)
- `summary` (JSON, nullable) - aggregated user permissions
- `is_allowed` (boolean, default false)
- `route_name` (string, nullable)
- `request_path` (string, nullable)
- `request_method` (string, nullable)
- `client_ip` (string, nullable)
- `user_agent` (string, nullable)
- `created_at` (timestamp)
- `updated_at` (timestamp)
- Indexes:
- Index on `user_id` + `created_at` (for efficient last failure lookup)
- Index on `auth_object_code` (for reporting)
- Use `Schema::connection('authorization')` since this is authorization-related data

### 3. Eloquent Model: `AuthorizationFailure`

**File**: `Modules/AuthorizationDebug/app/Entities/AuthorizationFailure.php`

- Connection: `authorization`
- Fillable fields: all except `id`, `created_at`, `updated_at`
- Relationship: `belongsTo(User::class)` - nullable
- Casts: `required_fields` and `summary` as `array`
- Casts: `is_allowed` as `boolean`
- Casts: `created_at` and `updated_at` as `datetime`

### 4. Extend AuthorizationService with Logging

**File**: `Modules/Authorization/app/Services/AuthorizationService.php`

- Add protected method `logAuthorizationCheck()`:
- Parameters: `User|UserDTO|int $user`, `string $objectCode`, `array $requiredFields`, `bool $isAllowed`, `array $authorizations`
- Build summary JSON from `$authorizations` (aggregate field rules)
- Extract request context (route, path, method, IP, user agent)
- Create `AuthorizationFailure` record with exception safety (try-catch)
- Only log if user is authenticated (has user_id)

- Modify `check()` method:
- After loading `$authorizations` (line 46-51), store them for logging
- After `checkAuthorizations()` returns result, call `logAuthorizationCheck()` with the result
- Ensure logging doesn't affect authorization decision (best-effort)

- Add protected method `buildSummaryFromAuthorizations()`:
- Parameters: `Collection $authorizations`, `array $requiredFields`
- For each field in `$requiredFields`, collect all rules from all authorizations
- Structure: `{ "ACTVT": { "rules": [ { "operator": "in", "values": ["01","02","03"] } ] } }`
- Handle operators: `*`, `=`, `in`, `between`
- Return structured array

### 5. AuthorizationDebugService

**File**: `Modules/AuthorizationDebug/app/Services/AuthorizationDebugService.php`

- `getLastFailureForUser(User|int $user): ?AuthorizationFailure`
- Query `authorization_failures` where `user_id` matches
- Order by `created_at DESC` or `id DESC`
- Return latest record or null

- `getLastFailureForUserId(int $userId): ?AuthorizationFailure`
- Same as above but accepts user ID directly

- `getFailuresForUser(User|int $user, int $limit = 10): Collection`
- Optional: Get multiple recent failures (for future enhancement)

### 6. Controller: `AuthorizationDebugController`

**File**: `Modules/AuthorizationDebug/app/Http/Controllers/AuthorizationDebugController.php`

- `showSelf(): View`
- Requires `auth` middleware
- Get current user via `auth()->user()`
- Call `AuthorizationDebugService::getLastFailureForUser()`
- If no failure: show friendly message
- If failure exists: render `authorization-debug::su53` view with failure data

- `showUser(User $user): View`
- Requires admin authorization (use Gate `super-admin` or SAP-style auth object)
- Call `AuthorizationDebugService::getLastFailureForUser($user)`
- Render same view with indication of whose failure is shown

### 7. Routes

**File**: `Modules/AuthorizationDebug/routes/web.php`

- `GET /auth/su53` → `AuthorizationDebugController@showSelf`
- Middleware: `auth`
- Name: `authorization-debug.su53`

- `GET /auth/su53/{user}` → `AuthorizationDebugController@showUser`
- Middleware: `auth`, Gate check for admin access
- Name: `authorization-debug.su53.user`
- Route model binding for `User`

### 8. Blade View: SU53 Display

**File**: `Modules/AuthorizationDebug/resources/views/su53.blade.php`

- Extend appropriate layout (likely `ui::layouts.app`)
- Display:
- Header: "Authorization Check Analysis (SU53-style)"
- User name and ID
- Timestamp of failure
- Auth object code
- Request context: path, route name, HTTP method, IP, user agent
- `is_allowed` status
- Table of required fields:
  - Column: Field code
  - Column: Required value
  - Column: User's allowed values (from summary JSON)
  - Column: Match status (MATCHED/NOT MATCHED)
- Handle missing/partial summary data gracefully
- Show "No authorization failures logged" message when appropriate
- Support dark mode (Tailwind CSS v4)

### 9. Update 403 Error View (Optional Enhancement)

**File**: `resources/views/errors/403.blade.php` (create if doesn't exist)

- Check if user is authenticated
- Check if `AuthorizationFailure` exists for current user (via service)
- If both true, show link: "Analyze last authorization failure (SU53)" → `route('authorization-debug.su53')`
- Optionally check `APP_ENV !== 'production'` or make it configurable

### 10. Factory and Seeder (Optional)

**File**: `Modules/AuthorizationDebug/database/factories/AuthorizationFailureFactory.php`

- Create factory for testing

### 11. Tests

**File**: `Modules/AuthorizationDebug/tests/Feature/AuthorizationDebugTest.php`

- Test `AuthorizationDebugService`:
- `getLastFailureForUser()` returns latest failure
- Returns null when no failures exist
- Correctly orders by created_at DESC

- Test `AuthorizationService` logging:
- When check passes: creates record with `is_allowed = true`
- When check fails: creates record with `is_allowed = false`
- Correctly stores `required_fields` JSON
- Correctly builds `summary` JSON
- Stores request context (route, path, method, IP, user agent)
- Logging failure doesn't break authorization check

- Test SU53 routes:
- Authenticated user can access `/auth/su53`
- Shows failure details when failure exists
- Shows "no failures" message when none exist
- Admin can access `/auth/su53/{user}`
- Non-admin cannot access `/auth/su53/{user}` (403)

- Test 403 error page:
- Shows SU53 link when user has failure
- Doesn't show link when no failure exists

### 12. Service Provider Updates

**File**: `Modules/AuthorizationDebug/app/Providers/AuthorizationDebugServiceProvider.php`

- Register routes
- Register service bindings if needed
- Load views with namespace `authorization-debug`

### 13. Module Configuration

- Update `composer.json` autoloading for `AuthorizationDebug` module
- Ensure module is enabled
- Update `.gitignore` if needed for module-specific files

## Implementation Notes

- **Exception Safety**: Authorization logging must not break authorization checks. Use try-catch in `logAuthorizationCheck()`.
- **Performance**: Logging should be lightweight. Consider queuing if needed in future.
- **Summary Aggregation**: The summary JSON should aggregate all field rules from all user's role authorizations for the object, showing what the user "has" vs what was "required".
- **Database Connection**: Use `authorization` connection for `authorization_failures` table since it's authorization-related data.
- **User Context**: Only log when user is authenticated (has user_id). Guest failures can be logged with `user_id = null` but focus on authenticated users.
- **Caching**: Don't cache failure logs - they should be real-time for debugging.
- **Security**: Admin route should be protected with Gate or SAP-style authorization check.

## Files to Create/Modify

### New Files:

1. `Modules/AuthorizationDebug/database/migrations/YYYY_MM_DD_HHMMSS_create_authorization_failures_table.php`
2. `Modules/AuthorizationDebug/app/Entities/AuthorizationFailure.php`
3. `Modules/AuthorizationDebug/app/Services/AuthorizationDebugService.php`
4. `Modules/AuthorizationDebug/app/Http/Controllers/AuthorizationDebugController.php`
5. `Modules/AuthorizationDebug/routes/web.php`
6. `Modules/AuthorizationDebug/resources/views/su53.blade.php`
7. `Modules/AuthorizationDebug/database/factories/AuthorizationFailureFactory.php` (optional)
8. `Modules/AuthorizationDebug/tests/Feature/AuthorizationDebugTest.php`
9. `resources/views/errors/403.blade.php` (if doesn't exist)

### Modified Files:

1. `Modules/Authorization/app/Services/AuthorizationService.php` - Add logging methods
2. `Modules/AuthorizationDebug/app/Providers/AuthorizationDebugServiceProvider.php` - Register routes and services

## Testing Strategy

- Unit tests for `AuthorizationDebugService`
- Feature tests for `AuthorizationService` logging behavior
- Feature tests for SU53 routes and views
- Test exception safety (logging failure doesn't break auth check)
- Test summary aggregation logic
- Test admin authorization for user-specific SU53 view

### To-dos

- [ ] Create Authorization module using php artisan module:make Authorization
- [ ] Create all database migrations (roles, role_user pivot, auth_objects, auth_object_fields, role_authorizations, role_authorization_fields)
- [ ] Create Eloquent entities in Modules/Authorization/app/Entities/ with all relationships
- [ ] Create HasRolesAndAuthorizations trait in app/Models/Concerns/ and add to User model
- [ ] Implement AuthorizationService in Modules/Authorization/app/Services/ with check() method, operator logic, and caching
- [ ] Modify AuthorizationServiceProvider to extend AuthServiceProvider, register auth-object gate, and register policies
- [ ] Create OrderPolicy example in Modules/Authorization/app/Policies/ demonstrating authorization checks
- [ ] Create AuthObjectMiddleware in Modules/Authorization/app/Http/Middleware/ for route-level checks
- [ ] Create config/auth_objects.php in Modules/Authorization/config/ with activity codes
- [ ] Create admin controllers (AuthObjectController, RoleController, RoleAuthorizationController, UserRoleController)
- [ ] Create Form Request classes in Modules/Authorization/app/Http/Requests/Admin/ for validation
- [ ] Create Blade views in Modules/Authorization/resources/views/admin/ for all CRUD screens
- [ ] Add admin routes to Modules/Authorization/routes/web.php
- [ ] Create factories in Modules/Authorization/database/factories/ for all models
- [ ] Create comprehensive AuthorizationSeeder in Modules/Authorization/database/seeders/ with example data covering all scenarios
- [ ] Create feature tests in Modules/Authorization/tests/Feature/ for AuthorizationService, OrderPolicy, AuthObjectMiddleware, and Gate
- [ ] Create Order model and migration in app/ for policy demonstration
- [ ] Update composer.json to add Authorization module autoloading
- [ ] Register middleware alias in AuthorizationServiceProvider