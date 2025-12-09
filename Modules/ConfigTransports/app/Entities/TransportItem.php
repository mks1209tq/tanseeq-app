<?php

namespace Modules\ConfigTransports\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportItem extends Model
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
        'transport_request_id',
        'object_type',
        'identifier',
        'operation',
        'payload',
        'meta',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'identifier' => 'array',
            'payload' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * Get the transport request that owns this item.
     */
    public function transportRequest(): BelongsTo
    {
        return $this->belongsTo(TransportRequest::class);
    }
}

