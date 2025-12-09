<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    /**
     * The database connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'system';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'domain',
        'subdomain',
        'database_prefix',
        'status',
        'plan',
        'max_users',
        'expires_at',
        'settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
            'settings' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant's database path.
     */
    public function getDatabasePath(string $connection): string
    {
        // Use DIRECTORY_SEPARATOR for cross-platform compatibility
        $path = base_path('tenants'.DIRECTORY_SEPARATOR.$this->id.DIRECTORY_SEPARATOR.$connection.'.sqlite');
        
        // Normalize path separators (Windows uses backslashes, but Laravel expects forward slashes in some contexts)
        return str_replace('\\', '/', $path);
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Scope a query to only include active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\TenantFactory::new();
    }
}
