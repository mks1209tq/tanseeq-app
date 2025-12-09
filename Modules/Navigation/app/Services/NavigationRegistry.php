<?php

namespace Modules\Navigation\Services;

class NavigationRegistry
{
    protected array $items = [];

    /**
     * Register navigation metadata for a route.
     */
    public function register(string $routeName, array $metadata): void
    {
        $this->items[$routeName] = $metadata;
    }

    /**
     * Get metadata for a specific route.
     */
    public function getMetadata(string $routeName): ?array
    {
        return $this->items[$routeName] ?? null;
    }

    /**
     * Get all registered metadata.
     */
    public function getAllMetadata(): array
    {
        return $this->items;
    }
}

