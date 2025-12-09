# Microservices Architecture Documentation

This application has been refactored to be **microservices-ready**. It currently runs as a **modular monolith** but can be easily split into separate microservices when needed.

## Architecture Overview

### Current State: Modular Monolith
- All modules run in the same Laravel application
- Shared database (can be split later)
- Direct service calls with fallback to HTTP APIs
- Event-driven communication ready

### Future State: Microservices
- Each module becomes an independent Laravel application
- Separate databases per service
- HTTP API communication between services
- Message queue for async communication
- Service discovery and API Gateway

## Service Contracts

### Authentication Service Interface
Located at: `app/Contracts/Services/AuthenticationServiceInterface.php`

**Methods:**
- `getUserById(int $userId): ?UserDTO`
- `getUsersByIds(array $userIds): array<UserDTO>`
- `userHasRole(int $userId, string|array $roles): bool`
- `isSuperAdmin(int $userId): bool`
- `getUserRoles(int $userId): array<string>`

### Authorization Service Interface
Located at: `app/Contracts/Services/AuthorizationServiceInterface.php`

**Methods:**
- `check(int $userId, string $objectCode, array $requiredFields): bool`

## Service Implementations

### Local Services (Monolith Mode)

#### LocalAuthenticationService
Located at: `app/Services/Local/LocalAuthenticationService.php`

- **Monolith Mode**: Calls API controllers directly (no HTTP overhead)
- Maintains service boundaries by using API layer
- Performance: ~1-2ms (minimal overhead)
- Implements caching (5 minutes TTL)

#### LocalAuthorizationService
Located at: `app/Services/Local/LocalAuthorizationService.php`

- **Monolith Mode**: Calls API controllers directly (no HTTP overhead)
- Maintains service boundaries by using API layer
- Implements caching (5 minutes TTL)

### HTTP Clients (Microservice Mode)

#### AuthenticationServiceClient
Located at: `app/Services/Clients/AuthenticationServiceClient.php`

- **Microservice Mode Only**: Makes HTTP requests to Authentication service API
- Used when `AUTHENTICATION_SERVICE_MODE=microservice`
- Implements caching (5 minutes TTL)
- Handles errors gracefully with logging

#### AuthorizationServiceClient
Located at: `app/Services/Clients/AuthorizationServiceClient.php`

- **Microservice Mode Only**: Makes HTTP requests to Authorization service API
- Used when `AUTHORIZATION_SERVICE_MODE=microservice`
- Implements caching (5 minutes TTL)

## Configuration

### Service Configuration
Located at: `config/services.php`

```php
'authentication' => [
    'mode' => env('AUTHENTICATION_SERVICE_MODE', 'monolith'),
    'url' => env('AUTHENTICATION_SERVICE_URL', 'http://authentication-service.test'),
    'timeout' => env('AUTHENTICATION_SERVICE_TIMEOUT', 5),
],
```

### Environment Variables

Add to `.env`:

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

## Data Transfer Objects (DTOs)

### UserDTO
Located at: `app/DTOs/UserDTO.php`

Immutable data transfer object for user data:
- `id`: int
- `name`: string
- `email`: string
- `emailVerifiedAt`: ?string
- `roles`: array<string>

**Methods:**
- `fromArray(array $data): UserDTO`
- `toArray(): array`
- `hasRole(string|array $roles): bool`
- `isSuperAdmin(): bool`

## API Endpoints

### Authentication Service API
Base URL: `/api/v1`

- `GET /api/v1/users/{id}` - Get user by ID
- `POST /api/v1/users/batch` - Get multiple users by IDs
- `POST /api/v1/users/{id}/has-role` - Check if user has role
- `GET /api/v1/users/{id}/roles` - Get user roles

### Authorization Service API
Base URL: `/api/v1`

- `POST /api/v1/authorizations/check` - Check authorization
  ```json
  {
    "user_id": 1,
    "object_code": "SALES_ORDER_HEADER",
    "required_fields": {
      "ACTVT": "03",
      "COMP_CODE": "1000"
    }
  }
  ```

## Event-Driven Communication

### Events
Located in: `app/Events/`

- `UserCreated` - Dispatched when a user is created
- `UserUpdated` - Dispatched when a user is updated
- `UserRoleAssigned` - Dispatched when a role is assigned to a user

### Event Listeners
Located in: `app/Listeners/`

- `SyncUserToOtherServices` - Syncs user data to other services (in microservice mode)

## Migration Path to Microservices

### Step 1: Split Databases
1. Create separate databases for each service
2. Update migrations to target specific databases
3. Configure database connections per service

### Step 2: Extract Services
1. Copy each module to a new Laravel application
2. Update service URLs in configuration
3. Set service mode to `microservice`
4. Deploy each service independently

### Step 3: Implement Service Discovery
1. Use Consul, Eureka, or Kubernetes service discovery
2. Update service clients to use discovery
3. Implement health checks

### Step 4: Add Message Queue
1. Set up RabbitMQ, Redis Queue, or AWS SQS
2. Replace direct HTTP calls with queue messages for async operations
3. Implement event sourcing if needed

### Step 5: API Gateway
1. Implement API Gateway (Kong, AWS API Gateway, etc.)
2. Route requests to appropriate services
3. Handle authentication/authorization at gateway level
4. Implement rate limiting and throttling

## Best Practices

### ✅ DO:
- Use service interfaces for all inter-service communication
- Always use DTOs for data transfer
- Implement proper error handling and retries
- Use caching to reduce service calls
- Log all inter-service communications
- Use events for async operations

### ❌ DON'T:
- Directly access models from other modules
- Use Eloquent relationships across services
- Share database connections between services
- Make synchronous calls for non-critical operations
- Ignore service failures

## Testing

### Unit Tests
Test service clients with mocked HTTP responses:
```php
Http::fake([
    'authentication-service.test/*' => Http::response(['data' => [...]])
]);
```

### Integration Tests
Test API endpoints with real service calls (in monolith mode).

## Monitoring

### Recommended Tools:
- **APM**: New Relic, Datadog, or Elastic APM
- **Logging**: ELK Stack, CloudWatch, or Splunk
- **Tracing**: Jaeger, Zipkin, or AWS X-Ray
- **Metrics**: Prometheus + Grafana

## Security

### Service-to-Service Authentication
- Use API keys or JWT tokens
- Implement mutual TLS (mTLS) for internal communication
- Rotate credentials regularly

### Rate Limiting
- Implement rate limiting per service
- Use circuit breakers to prevent cascade failures

## Performance

### Caching Strategy
- Cache user data for 5 minutes (configurable)
- Invalidate cache on user updates
- Use Redis for distributed caching

### Connection Pooling
- Reuse HTTP connections
- Implement connection pooling
- Set appropriate timeouts

## Support

For questions or issues, please refer to:
- Service contracts: `app/Contracts/Services/`
- Service clients: `app/Services/Clients/`
- Configuration: `config/services.php`

