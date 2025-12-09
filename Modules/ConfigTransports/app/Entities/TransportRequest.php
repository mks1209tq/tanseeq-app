<?php

namespace Modules\ConfigTransports\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Entities\User;
use Modules\ConfigTransports\Database\Factories\TransportRequestFactory;

class TransportRequest extends Model
{
    use HasFactory;

    /**
     * The database connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'config_transports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'number',
        'type',
        'status',
        'source_environment',
        'target_environments',
        'description',
        'created_by',
        'released_by',
        'released_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_environments' => 'array',
            'released_at' => 'datetime',
        ];
    }

    /**
     * Get the items for this transport request.
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransportItem::class);
    }

    /**
     * Get the user who created this transport request.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who released this transport request.
     */
    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /**
     * Scope a query to only include open transport requests.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to only include released transport requests.
     */
    public function scopeReleased($query)
    {
        return $query->where('status', 'released');
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if the transport request can be released.
     */
    public function canBeReleased(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if the transport request can be exported.
     */
    public function canBeExported(): bool
    {
        return $this->status === 'released';
    }

    /**
     * Release the transport request.
     */
    public function release(?int $releasedBy = null): void
    {
        if (! $this->canBeReleased()) {
            throw new \RuntimeException('Transport request cannot be released in current status.');
        }

        $this->update([
            'status' => 'released',
            'released_by' => $releasedBy ?? auth()->id(),
            'released_at' => now(),
        ]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return TransportRequestFactory::new();
    }
}

