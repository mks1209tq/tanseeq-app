# Performance Optimization: Local Service Implementation

## Overview

To maintain service boundaries while avoiding HTTP overhead in monolith mode, we've implemented **Local Service** implementations that call API controllers directly.

## Architecture

### Monolith Mode (Default)
- Uses `LocalAuthenticationService` and `LocalAuthorizationService`
- Calls API controllers directly (no HTTP)
- Performance: ~1-2ms per call
- Maintains service boundaries

### Microservice Mode
- Uses `AuthenticationServiceClient` and `AuthorizationServiceClient`
- Makes HTTP requests to remote services
- Performance: ~10-30ms per call (network overhead)
- Full service isolation

## Performance Comparison

| Approach | Latency | Service Boundaries | Notes |
|----------|---------|-------------------|-------|
| Direct Model Access | ~0.5ms | ❌ Broken | Fastest but violates boundaries |
| Local Service (Current) | ~1-2ms | ✅ Maintained | Best balance |
| HTTP to Localhost | ~10-30ms | ✅ Maintained | Too slow for monolith |

## Implementation Details

### Service Binding

The `AppServiceProvider` automatically selects the correct implementation:

```php
// Monolith mode
if ($authMode === 'monolith') {
    $this->app->singleton(
        AuthenticationServiceInterface::class,
        LocalAuthenticationService::class
    );
} else {
    // Microservice mode
    $this->app->singleton(
        AuthenticationServiceInterface::class,
        AuthenticationServiceClient::class
    );
}
```

### Local Service Implementation

```php
class LocalAuthenticationService implements AuthenticationServiceInterface
{
    public function getUserById(int $userId): ?UserDTO
    {
        // Call API controller directly (no HTTP)
        $controller = app(UserController::class);
        $response = $controller->show($userId);
        
        // Extract and return DTO
        return UserDTO::fromArray($response->getData()['data']);
    }
}
```

## Benefits

✅ **Maintains Service Boundaries** - All communication goes through API layer  
✅ **Minimal Performance Impact** - Only ~1-2ms overhead  
✅ **Same Code Path** - Easy to test and maintain  
✅ **No HTTP Overhead** - Direct controller calls  
✅ **Easy Migration** - Just change service mode config  

## Caching

Both local and HTTP clients implement aggressive caching:
- **TTL**: 5 minutes
- **Cache Keys**: User-specific and request-specific
- **Cache Invalidation**: Manual or TTL-based

## Testing

### Unit Tests
```php
// Mock the controller
$controller = Mockery::mock(UserController::class);
$controller->shouldReceive('show')->andReturn(response()->json(['data' => [...]]));

// Test local service
$service = new LocalAuthenticationService();
$user = $service->getUserById(1);
```

### Integration Tests
Test with real API controllers to ensure proper data flow.

## Migration Path

1. **Current (Monolith)**: Uses `LocalAuthenticationService`
2. **Future (Microservice)**: Change config to `microservice` mode
3. **Automatic Switch**: Service provider handles the switch

## Configuration

```env
# Monolith mode (uses local services)
AUTHENTICATION_SERVICE_MODE=monolith
AUTHORIZATION_SERVICE_MODE=monolith

# Microservice mode (uses HTTP clients)
AUTHENTICATION_SERVICE_MODE=microservice
AUTHENTICATION_SERVICE_URL=http://authentication-service.test
AUTHORIZATION_SERVICE_MODE=microservice
AUTHORIZATION_SERVICE_URL=http://authorization-service.test
```

## Performance Monitoring

Monitor these metrics:
- Service call latency
- Cache hit rates
- Error rates
- Service availability

## Best Practices

1. **Always use service interfaces** - Never call controllers directly
2. **Leverage caching** - Reduce redundant calls
3. **Batch operations** - Use `getUsersByIds()` instead of multiple `getUserById()` calls
4. **Error handling** - Graceful degradation on failures
5. **Logging** - Track all service calls for debugging

