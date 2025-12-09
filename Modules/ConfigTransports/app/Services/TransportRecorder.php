<?php

namespace Modules\ConfigTransports\Services;

use Modules\ConfigTransports\Contracts\Transportable;
use Modules\ConfigTransports\Entities\TransportItem;
use Modules\ConfigTransports\Entities\TransportRequest;

class TransportRecorder
{
    /**
     * Record a create operation for a transportable model.
     */
    public function recordCreate(Transportable $model): void
    {
        if (! $this->shouldRecord()) {
            return;
        }

        $request = $this->getActiveRequest($model);

        if (! $request) {
            return;
        }

        $this->createTransportItem($request, $model, 'create');
    }

    /**
     * Record an update operation for a transportable model.
     */
    public function recordUpdate(Transportable $model): void
    {
        if (! $this->shouldRecord()) {
            return;
        }

        $request = $this->getActiveRequest($model);

        if (! $request) {
            return;
        }

        $this->createTransportItem($request, $model, 'update');
    }

    /**
     * Record a delete operation for a transportable model.
     */
    public function recordDelete(Transportable $model): void
    {
        if (! $this->shouldRecord()) {
            return;
        }

        $request = $this->getActiveRequest($model);

        if (! $request) {
            return;
        }

        // For delete, we need to capture the identifier before the model is deleted
        $identifier = $model->getTransportIdentifier();
        $objectType = $model::getTransportObjectType();

        $this->createTransportItem($request, $model, 'delete', $identifier, $objectType);
    }

    /**
     * Get the active open transport request for the current context.
     */
    public function getActiveRequest(Transportable $model): ?TransportRequest
    {
        // For now, use a simple approach: get the current user's open request
        // or create a global open request per type
        $type = $this->getTransportTypeForModel($model);

        $request = TransportRequest::open()
            ->forType($type)
            ->where('created_by', auth()->id())
            ->latest()
            ->first();

        // If no open request exists, create one automatically
        if (! $request) {
            $request = $this->createOpenRequest($type);
        }

        return $request;
    }

    /**
     * Check if changes should be recorded.
     */
    protected function shouldRecord(): bool
    {
        // Only record in DEV environment
        $environmentRole = config('system.environment_role', 'dev');

        if ($environmentRole !== 'dev') {
            return false;
        }

        // Must be authenticated
        if (! auth()->check()) {
            return false;
        }

        return true;
    }

    /**
     * Create a transport item.
     */
    protected function createTransportItem(
        TransportRequest $request,
        Transportable $model,
        string $operation,
        ?array|string $identifier = null,
        ?string $objectType = null
    ): void {
        $identifier = $identifier ?? $model->getTransportIdentifier();
        $objectType = $objectType ?? $model::getTransportObjectType();
        $payload = in_array($operation, ['create', 'update']) ? $model->toTransportPayload() : null;

        TransportItem::create([
            'transport_request_id' => $request->id,
            'object_type' => $objectType,
            'identifier' => is_array($identifier) ? $identifier : ['key' => $identifier],
            'operation' => $operation,
            'payload' => $payload,
            'meta' => [
                'recorded_at' => now()->toIso8601String(),
                'recorded_by' => auth()->id(),
            ],
        ]);
    }

    /**
     * Get the transport type for a model.
     */
    protected function getTransportTypeForModel(Transportable $model): string
    {
        $objectType = $model::getTransportObjectType();

        // Map object types to transport types
        $typeMap = [
            'auth_object' => 'security',
            'auth_object_field' => 'security',
            'role' => 'security',
            'role_authorization' => 'security',
            'role_authorization_field' => 'security',
        ];

        return $typeMap[$objectType] ?? 'config';
    }

    /**
     * Create a new open transport request.
     */
    protected function createOpenRequest(string $type): TransportRequest
    {
        $number = $this->generateTransportNumber();

        return TransportRequest::create([
            'number' => $number,
            'type' => $type,
            'status' => 'open',
            'source_environment' => config('system.environment_role', 'dev'),
            'target_environments' => ['qa', 'prod'],
            'description' => 'Auto-created transport request',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Generate a transport number.
     *
     * Format: {TENANT_ID}_{ENV_PREFIX}K{SEQUENCE}
     * Example: 1_DEVK900001
     */
    protected function generateTransportNumber(): string
    {
        $tenant = app('tenant');
        $envPrefix = strtoupper(config('system.environment_role', 'dev'));
        $tenantPrefix = $tenant ? "{$tenant->id}_" : '';

        // Get the last transport number for this tenant and environment
        $lastNumber = TransportRequest::where('number', 'like', $tenantPrefix.$envPrefix.'K%')
            ->orderBy('number', 'desc')
            ->value('number');

        if ($lastNumber) {
            // Extract sequence number and increment
            $fullPrefix = $tenantPrefix.$envPrefix.'K';
            $sequence = (int) substr($lastNumber, strlen($fullPrefix));
            $sequence++;
        } else {
            $sequence = 900001;
        }

        return $tenantPrefix.$envPrefix.'K'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}

