# Authorization Flow Documentation

This document describes the process flow for authorization checks in the SAP-style authorization system, including both success and failure scenarios, and how the SU53-like debug feature tracks these checks.

## Table of Contents

1. [Authorization Success Flow](#authorization-success-flow)
2. [Authorization Failure Flow](#authorization-failure-flow)
3. [Logging Process](#logging-process)
4. [SU53 Debug View Flow](#su53-debug-view-flow)
5. [Entry Points](#entry-points)

---

## Authorization Success Flow

### Overview
When a user attempts to access a protected resource and has the required authorization, the system grants access and logs the successful check.

### Step-by-Step Process

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Request Arrives (Route/Middleware/Gate/Policy)               │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. Extract Authorization Context                                │
│    - Auth Object Code (e.g., "SALES_ORDER_HEADER")             │
│    - Required Fields (e.g., {"ACTVT": "03", "COMP_CODE": "1000"})│
│    - Current User                                               │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. AuthorizationService::check() Called                          │
│    Input: User, ObjectCode, RequiredFields                      │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. Check Super-Admin Status                                     │
│    - If SuperAdmin → Return true (bypass all checks)            │
│    - If not SuperAdmin → Continue to step 5                    │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. Check User Roles                                             │
│    - If no roles → Return false                                 │
│    - If has roles → Continue to step 6                          │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. Check Cache                                                  │
│    - Cache Key: "auth:{userId}:{objectCode}"                    │
│    - If cached → Use cached authorizations                      │
│    - If not cached → Load from database                         │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 7. Load Role Authorizations (if not cached)                     │
│    - Query RoleAuthorization for user's roles                   │
│    - Filter by AuthObject code                                  │
│    - Eager load field rules (RoleAuthorizationField)            │
│    - Cache results for 5 minutes                               │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 8. Evaluate Authorization Rules                                 │
│    For each RoleAuthorization:                                  │
│      - Group fields by field_code                               │
│      - For each required field:                                 │
│        * Check if field rules exist                             │
│        * Evaluate operators:                                     │
│          - "*" (wildcard) → Always matches                       │
│          - "=" (equals) → Exact match                            │
│          - "in" (list) → Value in comma-separated list          │
│          - "between" (range) → Value between from/to            │
│      - If all fields match → Authorization valid                │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 9. Authorization Result: TRUE                                   │
│    - At least one RoleAuthorization matches all required fields │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 10. Log Authorization Check (Exception-Safe)                    │
│     - Build summary from authorizations                         │
│     - Extract request context (route, path, method, IP, etc.)    │
│     - Create AuthorizationFailure record with:                   │
│       * is_allowed = true                                       │
│       * required_fields = {...}                                 │
│       * summary = {...} (aggregated user permissions)          │
│     - If logging fails → Continue (best-effort)                  │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 11. Return TRUE to Caller                                       │
│     - Middleware: Continue to next middleware/controller        │
│     - Gate: Return true                                         │
│     - Policy: Return true                                       │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 12. Request Proceeds                                            │
│     - Controller action executes                                │
│     - Response returned to user                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Key Points

- **Caching**: Authorization results are cached for 5 minutes to improve performance
- **Super-Admin Bypass**: Super-admin users bypass all authorization checks
- **Exception Safety**: Logging failures do not affect the authorization decision
- **Field-Level Matching**: All required fields must match for authorization to succeed

---

## Authorization Failure Flow

### Overview
When a user attempts to access a protected resource but lacks the required authorization, the system denies access, logs the failure, and optionally shows the SU53 debug view.

### Step-by-Step Process

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Request Arrives (Route/Middleware/Gate/Policy)               │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. Extract Authorization Context                                │
│    - Auth Object Code (e.g., "SALES_ORDER_HEADER")             │
│    - Required Fields (e.g., {"ACTVT": "03", "COMP_CODE": "1000"})│
│    - Current User                                               │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. AuthorizationService::check() Called                          │
│    Input: User, ObjectCode, RequiredFields                      │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. Check Super-Admin Status                                     │
│    - If SuperAdmin → Return true (bypass)                       │
│    - If not SuperAdmin → Continue                               │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. Check User Roles                                             │
│    - If no roles → Return false                                 │
│    - If has roles → Continue                                    │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. Load/Cache Role Authorizations                               │
│    - Check cache or load from database                          │
│    - Get all RoleAuthorizations for user's roles                │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 7. Evaluate Authorization Rules                                 │
│    For each RoleAuthorization:                                  │
│      - Check if all required fields match                        │
│      - Possible failure reasons:                                │
│        * No RoleAuthorization for this AuthObject               │
│        * Missing field rule for required field                   │
│        * Field value doesn't match (wrong operator/value)        │
│        * Field operator doesn't match required value             │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 8. Authorization Result: FALSE                                   │
│    - No RoleAuthorization matches all required fields           │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 9. Log Authorization Failure (Exception-Safe)                    │
│     - Build summary from authorizations (what user has)         │
│     - Extract request context                                   │
│     - Create AuthorizationFailure record with:                  │
│       * is_allowed = false                                     │
│       * required_fields = {...}                                │
│       * summary = {...} (aggregated permissions)              │
│       * route_name, request_path, request_method, etc.          │
│     - Store in authorization_failures table                    │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 10. Return FALSE to Caller                                      │
│     - Middleware: abort(403, 'Unauthorized')                    │
│     - Gate: Return false                                        │
│     - Policy: Return false                                      │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 11. HTTP 403 Forbidden Response                                 │
│     - Laravel renders 403 error view                           │
│     - Custom 403.blade.php checks for recent failure            │
│     - If failure exists → Show link to SU53 debug view          │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 12. User Sees 403 Error Page                                   │
│     - Error message displayed                                   │
│     - Optional: "Analyze Last Authorization Failure (SU53)" link│
└─────────────────────────────────────────────────────────────────┘
```

### Common Failure Scenarios

1. **No Roles Assigned**
   - User has no roles → Immediate failure
   - No RoleAuthorizations to check

2. **Missing Authorization Object**
   - User's roles don't have any RoleAuthorization for the requested AuthObject
   - Example: User tries to access "SALES_ORDER_HEADER" but has no authorizations for it

3. **Missing Field Rule**
   - RoleAuthorization exists but doesn't have a rule for a required field
   - Example: Authorization requires "ACTVT" but user's authorization has no ACTVT rule

4. **Field Value Mismatch**
   - Field rule exists but value doesn't match
   - Example: Required "ACTVT" = "03" but user only has "ACTVT" = "01,02"

5. **Operator Mismatch**
   - Field rule uses wrong operator for the required value
   - Example: Required "COMP_CODE" = "1000" but user has "COMP_CODE" between "2000-3000"

---

## Logging Process

### Overview
Every authorization check (success or failure) is logged to the `authorization_failures` table for debugging and analysis purposes.

### Logging Steps

1. **Extract User ID**
   ```php
   $userId = $this->getUserId($user);
   // Only log if user is authenticated (userId > 0)
   ```

2. **Build Summary from Authorizations**
   ```php
   $summary = $this->buildSummaryFromAuthorizations($authorizations, $requiredFields);
   ```
   - For each required field, collect all rules from all RoleAuthorizations
   - Structure: `{"ACTVT": {"rules": [{"operator": "in", "values": ["01","02","03"]}]}}`
   - Shows what the user "has" vs what was "required"

3. **Extract Request Context**
   ```php
   $routeName = optional(request()->route())->getName();
   $requestPath = request()->path();
   $requestMethod = request()->method();
   $clientIp = request()->ip();
   $userAgent = request()->userAgent();
   ```

4. **Create AuthorizationFailure Record**
   ```php
   AuthorizationFailure::create([
       'user_id' => $userId,
       'auth_object_code' => $objectCode,
       'required_fields' => $requiredFields,  // JSON
       'summary' => $summary,                  // JSON
       'is_allowed' => $isAllowed,             // boolean
       'route_name' => $routeName,
       'request_path' => $requestPath,
       'request_method' => $requestMethod,
       'client_ip' => $clientIp,
       'user_agent' => $userAgent,
   ]);
   ```

5. **Exception Safety**
   - Wrapped in try-catch block
   - Logging failures do not affect authorization decision
   - Best-effort logging (silent failure)

### Summary Structure

The `summary` field contains aggregated information about what the user has:

```json
{
  "ACTVT": {
    "rules": [
      {
        "operator": "in",
        "values": ["01", "02", "03"]
      }
    ]
  },
  "COMP_CODE": {
    "rules": [
      {
        "operator": "=",
        "values": ["1000"]
      },
      {
        "operator": "in",
        "values": ["2000", "3000"]
      }
    ]
  }
}
```

This allows the SU53 view to show:
- **Required**: What was checked
- **User Has**: What permissions the user actually has
- **Match Status**: Whether each field matched or not

---

## SU53 Debug View Flow

### Overview
The SU53-like debug view allows users (and admins) to view the last authorization failure for analysis and troubleshooting.

### Accessing SU53 View

1. **Self-View** (`/auth/su53`)
   - User views their own last failure
   - Requires authentication
   - No special permissions needed

2. **Admin View** (`/auth/su53/{user}`)
   - Admin views another user's last failure
   - Requires authentication + super-admin role
   - Protected by `super-admin` gate

### View Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. User Requests /auth/su53                                    │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. AuthorizationDebugController::showSelf()                      │
│    - Get current authenticated user                             │
│    - Call AuthorizationDebugService::getLastFailureForUser()     │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. AuthorizationDebugService::getLastFailureForUser()            │
│    - Query authorization_failures table                         │
│    - WHERE user_id = {userId}                                   │
│    - ORDER BY created_at DESC, id DESC                           │
│    - LIMIT 1                                                     │
│    - Return AuthorizationFailure or null                        │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. Controller Renders View                                      │
│    - If no failure → Show "No failures logged" message          │
│    - If failure exists → Render su53.blade.php                  │
└───────────────────────────────┬───────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. SU53 View Displays                                           │
│    - User information (name, ID)                                 │
│    - Timestamp of failure                                        │
│    - Authorization object code                                   │
│    - Request context (route, path, method, IP)                   │
│    - Result (ALLOWED/DENIED)                                     │
│    - Field-level analysis table:                                │
│      * Field code                                                │
│      * Required value                                            │
│      * User's allowed values (from summary)                     │
│      * Match status (MATCHED/NOT MATCHED)                       │
└─────────────────────────────────────────────────────────────────┘
```

### Field-Level Analysis

For each required field, the view:

1. **Extracts Required Value**
   - From `required_fields` JSON
   - Example: `{"ACTVT": "03"}` → Required: "03"

2. **Extracts User's Allowed Values**
   - From `summary` JSON
   - Example: `{"ACTVT": {"rules": [{"operator": "in", "values": ["01","02","03"]}]}}`
   - Shows: "01, 02, 03"

3. **Determines Match Status**
   - Compares required value against allowed values
   - Considers operator type:
     - `*` (wildcard) → Always MATCHED
     - `=` (equals) → MATCHED if required value equals allowed value
     - `in` (list) → MATCHED if required value in list
     - `between` (range) → MATCHED if required value between from/to
   - If no match → NOT MATCHED

4. **Displays Results**
   - Table format similar to SAP SU53
   - Color-coded status (green for MATCHED, red for NOT MATCHED)
   - Shows all allowed values for debugging

---

## Entry Points

### 1. Middleware (`AuthObjectMiddleware`)

```php
Route::middleware(['auth.object:SALES_ORDER_HEADER,03'])
    ->get('/sales-orders', [SalesOrderController::class, 'index']);
```

**Flow:**
- Middleware extracts object code and activity from route
- Calls `AuthorizationService::check()`
- If false → `abort(403)`
- If true → Continue to controller

### 2. Gate (`auth-object`)

```php
Gate::authorize('auth-object', 'SALES_ORDER_HEADER', ['ACTVT' => '03']);
```

**Flow:**
- Gate defined in `AuthorizationServiceProvider`
- Calls `AuthorizationService::check()`
- Returns boolean result

### 3. Policy Method

```php
public function viewAny(User $user): bool
{
    return Gate::allows('auth-object', 'SALES_ORDER_HEADER', ['ACTVT' => '01']);
}
```

**Flow:**
- Policy method calls Gate
- Gate calls `AuthorizationService::check()`
- Returns boolean result

### 4. Direct Service Call

```php
$authorizationService = app(AuthorizationService::class);
$allowed = $authorizationService->check($user, 'SALES_ORDER_HEADER', ['ACTVT' => '03']);
```

**Flow:**
- Direct call to service
- Returns boolean result
- Always logs the check

---

## Database Schema

### authorization_failures Table

```sql
CREATE TABLE authorization_failures (
    id BIGINT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULLABLE,
    auth_object_code VARCHAR(255) NOT NULL,
    required_fields JSON NOT NULL,
    summary JSON NULLABLE,
    is_allowed BOOLEAN DEFAULT FALSE,
    route_name VARCHAR(255) NULLABLE,
    request_path VARCHAR(255) NULLABLE,
    request_method VARCHAR(255) NULLABLE,
    client_ip VARCHAR(255) NULLABLE,
    user_agent TEXT NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_auth_object (auth_object_code)
);
```

---

## Performance Considerations

1. **Caching**
   - RoleAuthorizations cached for 5 minutes
   - Reduces database queries
   - Cache key: `auth:{userId}:{objectCode}`

2. **Exception Safety**
   - Logging wrapped in try-catch
   - Authorization decision never affected by logging failures
   - Best-effort logging approach

3. **Indexes**
   - `(user_id, created_at)` for efficient last failure lookup
   - `auth_object_code` for reporting and analysis

4. **Summary Aggregation**
   - Built during authorization check
   - Stored as JSON for fast retrieval
   - No additional queries needed for SU53 view

---

## Example Scenarios

### Scenario 1: Successful Authorization

**Request:** User with role "SALES_MANAGER" tries to display sales orders (ACTVT=01)

1. Middleware extracts: `SALES_ORDER_HEADER`, `{"ACTVT": "01"}`
2. Service checks user's RoleAuthorizations
3. Finds matching authorization with `ACTVT` = `*` (wildcard)
4. Result: **TRUE**
5. Logs: `is_allowed = true`, `summary = {"ACTVT": {"rules": [{"operator": "*"}]}}`
6. Request proceeds to controller

### Scenario 2: Failed Authorization

**Request:** User with role "SALES_MANAGER" tries to delete sales order (ACTVT=06)

1. Middleware extracts: `SALES_ORDER_HEADER`, `{"ACTVT": "06"}`
2. Service checks user's RoleAuthorizations
3. Finds authorization with `ACTVT` = `in:01,02,03` (only display, change, create)
4. Required `ACTVT=06` not in allowed list
5. Result: **FALSE**
6. Logs: `is_allowed = false`, `summary = {"ACTVT": {"rules": [{"operator": "in", "values": ["01","02","03"]}]}}`
7. `abort(403)` called
8. 403 error page shows link to SU53
9. User clicks link → Sees detailed analysis of why access was denied

### Scenario 3: Missing Field Rule

**Request:** User tries to access sales order for company "1000"

1. Middleware extracts: `SALES_ORDER_HEADER`, `{"ACTVT": "01", "COMP_CODE": "1000"}`
2. Service checks user's RoleAuthorizations
3. Finds authorization with `ACTVT` rule but **no** `COMP_CODE` rule
4. Result: **FALSE** (missing field rule)
5. Logs: `is_allowed = false`, `summary = {"ACTVT": {...}, "COMP_CODE": null}`
6. SU53 view shows: ACTVT MATCHED, COMP_CODE NOT MATCHED (no rules found)

---

## Troubleshooting Guide

### User sees "No authorization failures logged"

**Possible reasons:**
- User hasn't triggered any authorization checks yet
- All checks were successful (only failures are shown by default)
- Logging failed silently (check logs for errors)

### SU53 shows "NOT MATCHED" but user should have access

**Check:**
1. Verify user has correct roles assigned
2. Verify RoleAuthorization exists for the AuthObject
3. Verify RoleAuthorizationField rules match required values
4. Check operator type (wildcard vs. equals vs. in vs. between)
5. Verify cache hasn't cached stale data (clear cache)

### Authorization works but no log entry created

**Possible reasons:**
- User is not authenticated (user_id is null or 0)
- Database connection issue (check logs)
- Exception occurred during logging (check logs)
- Authorization check bypassed (super-admin)

---

## Summary

The authorization system provides:

1. **Comprehensive Authorization Checks**
   - Role-based access control
   - Field-level authorization rules
   - Multiple operator types (wildcard, equals, in, between)
   - Super-admin bypass

2. **Complete Audit Trail**
   - Every check logged (success and failure)
   - Request context captured
   - User permissions summarized

3. **Debugging Capabilities**
   - SU53-like view for failure analysis
   - Field-level comparison (required vs. allowed)
   - Match status indicators
   - Admin access to other users' failures

4. **Performance Optimizations**
   - Caching of authorization results
   - Exception-safe logging
   - Indexed database queries

This system enables fine-grained authorization control while providing powerful debugging tools for troubleshooting access issues.

