<?php

namespace Modules\Authorization\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Authorization\Database\Factories\AuthObjectFactory;
use Modules\ConfigTransports\Concerns\IsTransportable;
use Modules\ConfigTransports\Contracts\Transportable;

class AuthObject extends Model implements Transportable
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
        'code',
        'description',
    ];

    /**
     * Get the fields for this authorization object.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(AuthObjectField::class);
    }

    /**
     * Get the role authorizations for this authorization object.
     */
    public function roleAuthorizations(): HasMany
    {
        return $this->hasMany(RoleAuthorization::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AuthObjectFactory::new();
    }

    /**
     * Get the transport object type code.
     */
    public static function getTransportObjectType(): string
    {
        return 'auth_object';
    }

    /**
     * Get the transport identifier.
     */
    public function getTransportIdentifier(): array|string
    {
        return $this->code;
    }

    /**
     * Get the transport payload.
     */
    public function toTransportPayload(): array
    {
        return [
            'code' => $this->code,
            'description' => $this->description,
        ];
    }

    /**
     * Apply transport payload.
     */
    public static function applyTransportPayload(array|string $identifier, array $payload, string $operation): void
    {
        $code = is_array($identifier) ? ($identifier['code'] ?? $identifier['key'] ?? null) : $identifier;

        if (! $code) {
            throw new \InvalidArgumentException('Invalid identifier for AuthObject');
        }

        match ($operation) {
            'create', 'update' => static::updateOrCreate(
                ['code' => $code],
                [
                    'code' => $payload['code'] ?? $code,
                    'description' => $payload['description'] ?? null,
                ]
            ),
            'delete' => static::where('code', $code)->delete(),
            default => throw new \InvalidArgumentException("Unknown operation: {$operation}"),
        };
    }

    /**
     * Get transport dependencies.
     */
    public function getTransportDependencies(): array
    {
        return [];
    }
}

