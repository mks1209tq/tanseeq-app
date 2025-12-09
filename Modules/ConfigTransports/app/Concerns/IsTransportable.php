<?php

namespace Modules\ConfigTransports\Concerns;

use Modules\ConfigTransports\Contracts\Transportable;

/**
 * Trait to help Eloquent models implement the Transportable interface.
 */
trait IsTransportable
{
    /**
     * Get the transport object type code.
     *
     * Default implementation uses the model's table name.
     * Override in your model if needed.
     */
    public static function getTransportObjectType(): string
    {
        return (new static)->getTable();
    }

    /**
     * Get a stable, unique identifier for this object.
     *
     * Default implementation looks for 'code' or 'name' attribute.
     * Override in your model to provide custom identifier logic.
     */
    public function getTransportIdentifier(): array|string
    {
        if (isset($this->code)) {
            return $this->code;
        }

        if (isset($this->name)) {
            return $this->name;
        }

        // Fallback to ID if no natural key found
        return $this->id;
    }

    /**
     * Serialize the object for transport.
     *
     * Default implementation excludes IDs, timestamps, and pivot data.
     * Override in your model to customize what gets transported.
     */
    public function toTransportPayload(): array
    {
        $attributes = $this->getAttributes();

        // Remove ID and timestamps
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);

        // Remove pivot data if present
        if (isset($attributes['pivot'])) {
            unset($attributes['pivot']);
        }

        return $attributes;
    }

    /**
     * Get dependencies for this transportable object.
     *
     * Default implementation returns empty array.
     * Override in your model to define dependencies.
     */
    public function getTransportDependencies(): array
    {
        return [];
    }
}

