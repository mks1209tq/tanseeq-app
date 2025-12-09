<?php

namespace Modules\Navigation\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Authorization\Services\AuthorizationService;
use Modules\Navigation\Attributes\NavigationItem;
use ReflectionClass;

class NavigationService
{
    public function __construct(
        protected AuthorizationService $authorizationService,
        protected NavigationRegistry $registry
    ) {}

    /**
     * Discover all routes with NavigationItem attributes and build navigation items.
     */
    public function discoverNavigationItems(): array
    {
        $items = [];
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            // Only process named routes with GET method (for navigation)
            if (! $route->getName() || ! in_array('GET', $route->methods())) {
                continue;
            }

            // Check for NavigationItem attribute
            $attribute = $this->getNavigationAttribute($route);

            if (! $attribute) {
                continue;
            }

            // Extract authorization from middleware
            $authInfo = $this->extractAuthMiddleware($route);

            if (! $authInfo) {
                continue; // Skip routes without authorization
            }

            // Build navigation item with path
            $uri = $route->uri();
            $path = '/'.ltrim($uri, '/');

            $items[] = [
                'route' => $route->getName(),
                'label' => $attribute->label,
                'icon' => $attribute->icon,
                'order' => $attribute->order,
                'group' => $attribute->group,
                'auth_object' => $authInfo['object'],
                'activity_code' => $attribute->activityCode ?? $authInfo['activity'] ?? '03',
                'required_fields' => array_merge($authInfo['fields'] ?? [], $attribute->requiredFields),
                'uri' => $uri,
                'path' => $path,
                'show_in_navigation' => $attribute->showInNavigation,
            ];
        }

        // Sort by order, then by label
        usort($items, function ($a, $b) {
            if ($a['order'] !== $b['order']) {
                return $a['order'] <=> $b['order'];
            }

            return strcmp($a['label'], $b['label']);
        });

        return $items;
    }

    /**
     * Get NavigationItem attribute from route.
     */
    protected function getNavigationAttribute($route): ?NavigationItem
    {
        $routeName = $route->getName();

        // First check registry
        $metadata = $this->registry->getMetadata($routeName);

        if ($metadata) {
            return new NavigationItem(
                label: $metadata['label'],
                icon: $metadata['icon'] ?? null,
                order: $metadata['order'] ?? 100,
                group: $metadata['group'] ?? null,
                activityCode: $metadata['activity_code'] ?? '03',
                requiredFields: $metadata['required_fields'] ?? [],
                showInNavigation: $metadata['show_in_navigation'] ?? true,
            );
        }

        // Check controller method
        $action = $route->getAction();

        if (isset($action['controller'])) {
            $controllerAction = $action['controller'];

            // Handle different controller action formats
            // Old style: Controller@method
            // New style: Controller::method
            // Invokable: Controller (no method)
            if (str_contains($controllerAction, '@')) {
                $parts = explode('@', $controllerAction, 2);
                $controller = $parts[0] ?? null;
                $method = $parts[1] ?? null;
            } elseif (str_contains($controllerAction, '::')) {
                $parts = explode('::', $controllerAction, 2);
                $controller = $parts[0] ?? null;
                $method = $parts[1] ?? null;
            } else {
                // Invokable controller (no method specified)
                $controller = $controllerAction;
                $method = '__invoke';
            }

            if (! $controller) {
                return null;
            }

            try {
                $reflection = new ReflectionClass($controller);

                // Check method attribute if method is specified
                if ($method && $reflection->hasMethod($method)) {
                    $methodReflection = $reflection->getMethod($method);
                    $attributes = $methodReflection->getAttributes(NavigationItem::class);

                    if (! empty($attributes)) {
                        return $attributes[0]->newInstance();
                    }
                }

                // Check class attribute
                $classAttributes = $reflection->getAttributes(NavigationItem::class);
                if (! empty($classAttributes)) {
                    return $classAttributes[0]->newInstance();
                }
            } catch (\ReflectionException $e) {
                // Controller or method doesn't exist
            }
        }

        return null;
    }

    /**
     * Extract authorization middleware information from route.
     */
    protected function extractAuthMiddleware($route): ?array
    {
        $middleware = $route->gatherMiddleware();

        foreach ($middleware as $middlewareItem) {
            if (is_string($middlewareItem)) {
                // Handle "auth.object:COMPANY_MANAGEMENT" or "auth.object:COMPANY_MANAGEMENT:03"
                if (preg_match('/^auth\.object:(.+?)(?::(.+))?$/', $middlewareItem, $matches)) {
                    return [
                        'object' => $matches[1],
                        'activity' => $matches[2] ?? '03',
                        'fields' => [],
                    ];
                }
            }
        }

        // Also check route action middleware
        $actionMiddleware = $route->getAction('middleware') ?? [];
        foreach ($actionMiddleware as $middlewareItem) {
            if (is_string($middlewareItem) && preg_match('/^auth\.object:(.+?)(?::(.+))?$/', $middlewareItem, $matches)) {
                return [
                    'object' => $matches[1],
                    'activity' => $matches[2] ?? '03',
                    'fields' => [],
                ];
            }
        }

        return null;
    }

    /**
     * Get authorized navigation items for current user.
     */
    public function getAuthorizedItems(?string $group = null): array
    {
        $items = $this->discoverNavigationItems();
        $filtered = $this->filterAuthorized($items);

        // Filter by group if specified
        if ($group !== null) {
            $filtered = array_filter($filtered, fn ($item) => ($item['group'] ?? null) === $group);
        }

        return array_values($filtered);
    }

    /**
     * Filter items based on user authorization.
     */
    protected function filterAuthorized(array $items): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        return array_filter($items, function ($item) use ($user) {
            if (! $item['show_in_navigation']) {
                return false;
            }

            $fields = $item['required_fields'] ?? [];
            $fields['ACTVT'] = $item['activity_code'] ?? '03';

            return $this->authorizationService->check(
                $user,
                $item['auth_object'],
                $fields
            );
        });
    }

    /**
     * Get navigation items grouped by group.
     */
    public function getGroupedItems(): array
    {
        $items = $this->getAuthorizedItems();
        $grouped = [];

        foreach ($items as $item) {
            $group = $item['group'] ?? 'main';
            if (! isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $item;
        }

        return $grouped;
    }

    /**
     * Check if current user can access a navigation item.
     */
    public function canAccess(string $authObject, ?string $activityCode = '03', array $requiredFields = []): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $fields = $requiredFields;
        if ($activityCode !== null) {
            $fields['ACTVT'] = $activityCode;
        }

        return $this->authorizationService->check($user, $authObject, $fields);
    }
}
