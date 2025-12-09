<?php

namespace Modules\ConfigTransports\Contracts;

/**
 * Interface for models that can be transported between environments.
 */
interface Transportable
{
    /**
     * Get the transport object type code.
     *
     * @return string A short code identifying the type (e.g., 'auth_object', 'role')
     */
    public static function getTransportObjectType(): string;

    /**
     * Get a stable, unique identifier for this object within its type.
     *
     * @return array|string Natural key(s) for the object (e.g., 'code', ['code' => 'SALES_ORDER_HEADER'])
     */
    public function getTransportIdentifier(): array|string;

    /**
     * Serialize the object for transport.
     *
     * @return array All fields needed to recreate/update this object (excluding IDs, timestamps, etc.)
     */
    public function toTransportPayload(): array;

    /**
     * Apply a transport payload to create/update/delete the object.
     *
     * @param  array|string  $identifier  The natural key(s) for the object
     * @param  array  $payload  The data to apply
     * @param  string  $operation  The operation: 'create', 'update', or 'delete'
     */
    public static function applyTransportPayload(array|string $identifier, array $payload, string $operation): void;

    /**
     * Get dependencies for this transportable object.
     *
     * @return array<array{type: string, identifier: array|string}> Array of dependent object types and identifiers
     */
    public function getTransportDependencies(): array;
}

