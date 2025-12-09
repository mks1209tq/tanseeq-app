<?php

namespace Modules\ConfigTransports\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Entities\User;

class TransportImportLog extends Model
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
        'transport_number',
        'import_environment',
        'imported_by',
        'status',
        'summary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'summary' => 'array',
        ];
    }

    /**
     * Get the user who imported this transport.
     */
    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}

