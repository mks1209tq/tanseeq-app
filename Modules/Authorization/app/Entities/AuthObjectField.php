<?php

namespace Modules\Authorization\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authorization\Database\Factories\AuthObjectFieldFactory;
use Modules\ConfigTransports\Concerns\IsTransportable;
use Modules\ConfigTransports\Contracts\Transportable;

class AuthObjectField extends Model implements Transportable
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
        'auth_object_id',
        'code',
        'label',
        'is_org_level',
        'sort',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_org_level' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * Get the authorization object that owns this field.
     */
    public function authObject(): BelongsTo
    {
        return $this->belongsTo(AuthObject::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AuthObjectFieldFactory::new();
    }

    /**
     * Get the transport object type code.
     */
    public static function getTransportObjectType(): string
    {
        return 'auth_object_field';
    }

    /**
     * Get the transport identifier.
     */
    public function getTransportIdentifier(): array|string
    {
        return [
            'auth_object_code' => $this->authObject->code,
            'code' => $this->code,
        ];
    }

    /**
     * Get the transport payload.
     */
    public function toTransportPayload(): array
    {
        return [
            'auth_object_code' => $this->authObject->code,
            'code' => $this->code,
            'label' => $this->label,
            'is_org_level' => $this->is_org_level,
            'sort' => $this->sort,
        ];
    }

    /**
     * Apply transport payload.
     */
    public static function applyTransportPayload(array|string $identifier, array $payload, string $operation): void
    {
        $identifier = is_array($identifier) ? $identifier : ['key' => $identifier];
        $authObjectCode = $identifier['auth_object_code'] ?? $payload['auth_object_code'] ?? null;
        $code = $identifier['code'] ?? $payload['code'] ?? null;

        if (! $authObjectCode || ! $code) {
            throw new \InvalidArgumentException('Invalid identifier for AuthObjectField');
        }

        $authObject = AuthObject::where('code', $authObjectCode)->first();

        if (! $authObject) {
            throw new \RuntimeException("AuthObject '{$authObjectCode}' not found");
        }

        match ($operation) {
            'create', 'update' => static::updateOrCreate(
                [
                    'auth_object_id' => $authObject->id,
                    'code' => $code,
                ],
                [
                    'label' => $payload['label'] ?? null,
                    'is_org_level' => $payload['is_org_level'] ?? false,
                    'sort' => $payload['sort'] ?? 0,
                ]
            ),
            'delete' => static::where('auth_object_id', $authObject->id)
                ->where('code', $code)
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
            ['type' => 'auth_object', 'identifier' => $this->authObject->code],
        ];
    }
}

