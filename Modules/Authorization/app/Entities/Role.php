<?php

namespace Modules\Authorization\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Authorization\Database\Factories\RoleFactory;
use Modules\ConfigTransports\Concerns\IsTransportable;
use Modules\ConfigTransports\Contracts\Transportable;

class Role extends Model implements Transportable
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
        'name',
        'description',
    ];

    /**
     * Get the users that belong to this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Authentication\Entities\User::class,
            'role_user', // pivot table
            'role_id', // foreign key on pivot table
            'user_id' // related key on pivot table
        );
    }

    /**
     * Get the role authorizations for this role.
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
        return RoleFactory::new();
    }

    /**
     * Get the transport object type code.
     */
    public static function getTransportObjectType(): string
    {
        return 'role';
    }

    /**
     * Get the transport identifier.
     */
    public function getTransportIdentifier(): array|string
    {
        return $this->name;
    }

    /**
     * Get the transport payload.
     */
    public function toTransportPayload(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    /**
     * Apply transport payload.
     */
    public static function applyTransportPayload(array|string $identifier, array $payload, string $operation): void
    {
        $name = is_array($identifier) ? ($identifier['name'] ?? $identifier['key'] ?? null) : $identifier;

        if (! $name) {
            throw new \InvalidArgumentException('Invalid identifier for Role');
        }

        match ($operation) {
            'create', 'update' => static::updateOrCreate(
                ['name' => $name],
                [
                    'name' => $payload['name'] ?? $name,
                    'description' => $payload['description'] ?? null,
                ]
            ),
            'delete' => static::where('name', $name)->delete(),
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
