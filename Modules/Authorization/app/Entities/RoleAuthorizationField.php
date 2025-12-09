<?php

namespace Modules\Authorization\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authorization\Database\Factories\RoleAuthorizationFieldFactory;
use Modules\ConfigTransports\Concerns\IsTransportable;
use Modules\ConfigTransports\Contracts\Transportable;

class RoleAuthorizationField extends Model implements Transportable
{
    use HasFactory, IsTransportable;

    /**
     * The database connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'authorization';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_authorization_id',
        'field_code',
        'operator',
        'value_from',
        'value_to',
    ];

    /**
     * Get the role authorization that owns this field rule.
     */
    public function roleAuthorization(): BelongsTo
    {
        return $this->belongsTo(RoleAuthorization::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return RoleAuthorizationFieldFactory::new();
    }

    /**
     * Get the transport object type code.
     */
    public static function getTransportObjectType(): string
    {
        return 'role_authorization_field';
    }

    /**
     * Get the transport identifier.
     */
    public function getTransportIdentifier(): array|string
    {
        return [
            'role_name' => $this->roleAuthorization->role->name,
            'auth_object_code' => $this->roleAuthorization->authObject->code,
            'field_code' => $this->field_code,
        ];
    }

    /**
     * Get the transport payload.
     */
    public function toTransportPayload(): array
    {
        return [
            'role_name' => $this->roleAuthorization->role->name,
            'auth_object_code' => $this->roleAuthorization->authObject->code,
            'field_code' => $this->field_code,
            'operator' => $this->operator,
            'value_from' => $this->value_from,
            'value_to' => $this->value_to,
        ];
    }

    /**
     * Apply transport payload.
     */
    public static function applyTransportPayload(array|string $identifier, array $payload, string $operation): void
    {
        $identifier = is_array($identifier) ? $identifier : ['key' => $identifier];
        $roleName = $identifier['role_name'] ?? $payload['role_name'] ?? null;
        $authObjectCode = $identifier['auth_object_code'] ?? $payload['auth_object_code'] ?? null;
        $fieldCode = $identifier['field_code'] ?? $payload['field_code'] ?? null;

        if (! $roleName || ! $authObjectCode || ! $fieldCode) {
            throw new \InvalidArgumentException('Invalid identifier for RoleAuthorizationField');
        }

        $role = Role::where('name', $roleName)->first();
        $authObject = AuthObject::where('code', $authObjectCode)->first();

        if (! $role) {
            throw new \RuntimeException("Role '{$roleName}' not found");
        }

        if (! $authObject) {
            throw new \RuntimeException("AuthObject '{$authObjectCode}' not found");
        }

        $roleAuthorization = RoleAuthorization::where('role_id', $role->id)
            ->where('auth_object_id', $authObject->id)
            ->first();

        if (! $roleAuthorization) {
            throw new \RuntimeException("RoleAuthorization not found for Role '{$roleName}' and AuthObject '{$authObjectCode}'");
        }

        match ($operation) {
            'create', 'update' => static::updateOrCreate(
                [
                    'role_authorization_id' => $roleAuthorization->id,
                    'field_code' => $fieldCode,
                ],
                [
                    'operator' => $payload['operator'] ?? null,
                    'value_from' => $payload['value_from'] ?? null,
                    'value_to' => $payload['value_to'] ?? null,
                ]
            ),
            'delete' => static::where('role_authorization_id', $roleAuthorization->id)
                ->where('field_code', $fieldCode)
                ->delete(),
            default => throw new \InvalidArgumentException("Unknown operation: {$operation}"),
        };
    }

    /**
     * Get transport dependencies.
     */
    public function getTransportDependencies(): array
    {
        return [
            ['type' => 'role', 'identifier' => $this->roleAuthorization->role->name],
            ['type' => 'auth_object', 'identifier' => $this->roleAuthorization->authObject->code],
            ['type' => 'role_authorization', 'identifier' => [
                'role_name' => $this->roleAuthorization->role->name,
                'auth_object_code' => $this->roleAuthorization->authObject->code,
            ]],
        ];
    }
}

