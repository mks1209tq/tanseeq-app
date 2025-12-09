<?php

namespace Modules\Navigation\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Authorization\Services\AuthorizationService;
use ReflectionClass;
use ReflectionMethod;

class QuickLaunchService
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Discover all resource routes and build model registry.
     */
    public function discoverModels(): array
    {
        $models = [];
        $routes = Route::getRoutes();
        $processedResources = [];

        foreach ($routes as $route) {
            $action = $route->getAction();
            $uri = $route->uri();
            $routeName = $route->getName();

            // Skip API routes
            if (str_starts_with($uri, 'api/') || str_starts_with($uri, 'v1/')) {
                continue;
            }

            // Check if route name suggests a resource (e.g., 'todo.index', 'company.show')
            if ($routeName) {
                $nameParts = explode('.', $routeName);
                if (count($nameParts) === 2) {
                    $resourceName = $nameParts[0];
                    $method = $nameParts[1];

                    // Check if it's a resource method
                    $resourceMethods = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
                    if (in_array($method, $resourceMethods) && ! isset($processedResources[$resourceName])) {
                        $processedResources[$resourceName] = true;

                        // Extract route prefix from URI
                        $routePrefix = $this->extractRoutePrefix($uri, $method);

                        if ($routePrefix) {
                            // Check authorization - extract auth object from route middleware
                            $authObject = $this->extractAuthObject($route);
                            
                            if ($authObject) {
                                // Check if current user has access to this resource
                                if (! $this->checkAuthorization($authObject)) {
                                    continue; // Skip this model if user doesn't have access
                                }
                            }

                            // Get singular model key
                            $modelKey = $this->singularize($resourceName);

                            // Get controller from route action
                            if (isset($action['controller'])) {
                                $controller = $action['controller'];
                                $controllerClass = $this->extractControllerClass($controller);

                                if ($controllerClass) {
                                    try {
                                        $reflection = new ReflectionClass($controllerClass);
                                        $modelInfo = $this->extractModelInfo($reflection, $modelKey, $routePrefix);

                                        if ($modelInfo) {
                                            $models[$modelKey] = $modelInfo;
                                        }
                                    } catch (\ReflectionException $e) {
                                        // Controller doesn't exist, skip
                                        continue;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $models;
    }

    /**
     * Extract route prefix from URI.
     */
    protected function extractRoutePrefix(string $uri, string $method): ?string
    {
        $uriParts = explode('/', trim($uri, '/'));

        // For index/create/store, the prefix is usually the first non-empty part
        if (in_array($method, ['index', 'create', 'store'])) {
            foreach ($uriParts as $part) {
                if (! empty($part) && ! str_starts_with($part, '{')) {
                    return $part;
                }
            }
        }

        // For show/edit/update/destroy, it's usually before the parameter
        foreach ($uriParts as $index => $part) {
            if (str_starts_with($part, '{')) {
                // Return the previous part
                if ($index > 0) {
                    return $uriParts[$index - 1];
                }
            }
        }

        // Fallback: return first non-empty part
        foreach ($uriParts as $part) {
            if (! empty($part) && ! str_starts_with($part, '{')) {
                return $part;
            }
        }

        return null;
    }

    /**
     * Extract controller class from controller string.
     */
    protected function extractControllerClass(string $controller): ?string
    {
        if (str_contains($controller, '@')) {
            return explode('@', $controller, 2)[0];
        } elseif (str_contains($controller, '::')) {
            return explode('::', $controller, 2)[0];
        }

        return null;
    }

    /**
     * Extract model information from controller.
     */
    protected function extractModelInfo(ReflectionClass $controller, string $modelKey, string $routePrefix): ?array
    {
        // Check if controller has a model property or uses model binding
        $modelClass = null;
        $modelName = $this->formatModelName($modelKey);

        // Try to find the model class from controller methods
        try {
            $storeMethod = $controller->getMethod('store');
            $storeParams = $storeMethod->getParameters();
            
            // Look for FormRequest that might have validation rules
            foreach ($storeParams as $param) {
                $paramClass = $param->getType()?->getName();
                if ($paramClass && class_exists($paramClass)) {
                    $requestReflection = new ReflectionClass($paramClass);
                    if ($requestReflection->hasMethod('rules')) {
                        $rules = $this->extractRules($requestReflection);
                        $createFields = $this->buildCreateFields($rules);
                        
                        return [
                            'name' => $modelName,
                            'routePrefix' => $routePrefix,
                            'searchFields' => $this->inferSearchFields($rules),
                            'createFields' => $createFields,
                        ];
                    }
                }
            }
        } catch (\ReflectionException $e) {
            // Method doesn't exist or can't be accessed
        }

        // Fallback: return basic info
        return [
            'name' => $modelName,
            'routePrefix' => $routePrefix,
            'searchFields' => ['name', 'title'],
            'createFields' => [
                'name' => ['required' => true, 'type' => 'text', 'description' => ucfirst($modelKey).' name'],
            ],
        ];
    }

    /**
     * Extract validation rules from FormRequest.
     */
    protected function extractRules(ReflectionClass $requestClass): array
    {
        try {
            // Use Laravel's container to resolve the FormRequest
            // This will inject the current request automatically
            $instance = app()->make($requestClass->getName());
            
            if (method_exists($instance, 'rules')) {
                return $instance->rules();
            }
        } catch (\Exception $e) {
            // Container resolution failed, return empty array
            // Fallback will use default fields
        }

        return [];
    }

    /**
     * Build create fields from validation rules.
     */
    protected function buildCreateFields(array $rules): array
    {
        $fields = [];

        foreach ($rules as $fieldName => $rule) {
            // Convert Laravel rules to our format
            $ruleArray = is_string($rule) ? explode('|', $rule) : (is_array($rule) ? $rule : []);
            
            $required = in_array('required', $ruleArray) || (is_string($rule) && str_contains($rule, 'required'));
            $type = 'text';
            
            // Determine field type
            if (in_array('date', $ruleArray) || (is_string($rule) && str_contains($rule, 'date'))) {
                $type = 'date';
            } elseif (in_array('email', $ruleArray) || (is_string($rule) && str_contains($rule, 'email'))) {
                $type = 'email';
            } elseif (in_array('numeric', $ruleArray) || (is_string($rule) && str_contains($rule, 'numeric'))) {
                $type = 'number';
            }

            // Special handling for todo date field
            if ($fieldName === 'due_date' && $type === 'date') {
                $fields['date'] = [
                    'required' => $required,
                    'type' => 'date',
                    'format' => 'MMDDYY',
                    'description' => 'Due date (MMDDYY)',
                ];
            } else {
                $fields[$fieldName] = [
                    'required' => $required,
                    'type' => $type,
                    'description' => ucfirst(str_replace('_', ' ', $fieldName)),
                ];
            }
        }

        return $fields;
    }

    /**
     * Infer search fields from validation rules.
     */
    protected function inferSearchFields(array $rules): array
    {
        $searchFields = [];
        
        // Common searchable field names
        $commonFields = ['name', 'title', 'description', 'email'];
        
        foreach (array_keys($rules) as $fieldName) {
            if (in_array($fieldName, $commonFields)) {
                $searchFields[] = $fieldName;
            }
        }

        // Default to common fields if none found
        if (empty($searchFields)) {
            $searchFields = ['name', 'title'];
        }

        return $searchFields;
    }

    /**
     * Convert plural to singular.
     */
    protected function singularize(string $word): string
    {
        // Simple singularization rules
        $rules = [
            'ies' => 'y',
            'es' => '',
            's' => '',
        ];

        foreach ($rules as $plural => $singular) {
            if (str_ends_with($word, $plural)) {
                return substr($word, 0, -strlen($plural)).$singular;
            }
        }

        return $word;
    }

    /**
     * Format model name for display.
     */
    protected function formatModelName(string $key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Extract authorization object from route middleware.
     */
    protected function extractAuthObject($route): ?string
    {
        $middleware = $route->gatherMiddleware();

        foreach ($middleware as $middlewareItem) {
            if (is_string($middlewareItem)) {
                // Handle "auth.object:TODO_MANAGEMENT" or "auth.object:TODO_MANAGEMENT:03"
                if (preg_match('/^auth\.object:(.+?)(?::(.+))?$/', $middlewareItem, $matches)) {
                    return $matches[1];
                }
            }
        }

        // Also check route action middleware
        $action = $route->getAction();
        $actionMiddleware = $action['middleware'] ?? [];
        
        foreach ($actionMiddleware as $middlewareItem) {
            if (is_string($middlewareItem) && preg_match('/^auth\.object:(.+?)(?::(.+))?$/', $middlewareItem, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Check if current user has authorization for the given object.
     */
    protected function checkAuthorization(string $authObject): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Check authorization for display access (ACTVT = '03')
        // This is the minimum required to see the model in quick launch
        return $this->authorizationService->check(
            $user,
            $authObject,
            ['ACTVT' => '03']
        );
    }
}

