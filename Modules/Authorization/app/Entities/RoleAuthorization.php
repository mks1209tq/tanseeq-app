<?php

namespace Modules\Authorization\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Authorization\Database\Factories\RoleAuthorizationFactory;
use Modules\ConfigTransports\Concerns\IsTransportable;
use Modules\ConfigTransports\Contracts\Transportable;

class RoleAuthorization extends Model implements Transportable
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
        'role_id',
        'auth_object_id',
        'label',
    ];

    /**
     * Get the role that owns this authorization.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the authorization object for this authorization.
     */
    public function authObject(): BelongsTo
    {
        return $this->belongsTo(AuthObject::class);
    }

    /**
     * Get the field rules for this authorization.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(RoleAuthorizationField::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return RoleAuthorizationFactory::new();
    }

    /**
     * Get the transport object type code.
     */
    public static function getTransportObjectType(): string
    {
        return 'role_authorization';
    }

    /**
     * Get the transport identifier.
     */
    public function getTransportIdentifier(): array|string
    {
        return [
            'role_name' => $this->role->name,
            'auth_object_code' => $this->authObject->code,
        ];
    }

    /**
     * Get the transport payload.
     */
    public function toTransportPayload(): array
    {
        return [
            'role_name' => $this->role->name,
            'auth_object_code' => $this->authObject->code,
            'label' => $this->label,
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

        if (! $roleName || ! $authObjectCode) {
            throw new \InvalidArgumentException('Invalid identifier for RoleAuthorization');
        }

        $role = Role::where('name', $roleName)->first();
        $authObject = AuthObject::where('code', $authObjectCode)->first();

        if (! $role) {
            throw new \RuntimeException("Role '{$roleName}' not found");
        }

        if (! $authObject) {
            throw new \RuntimeException("AuthObject '{$authObjectCode}' not found");
        }

        match ($operation) {
            'create', 'update' => static::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'auth_object_id' => $authObject->id,
                ],
                [
                    'label' => $payload['label'] ?? null,
                ]
            ),
            'delete' => static::where('role_id', $role->id)
                ->where('auth_object_id', $authObject->id)
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
            ['type' => 'role', 'identifier' => $this->role->name],
            ['type' => 'auth_object', 'identifier' => $this->authObject->code],
        ];
    }
}

