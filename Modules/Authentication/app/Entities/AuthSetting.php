<?php

namespace Modules\Authentication\Entities;

use Illuminate\Database\Eloquent\Model;

class AuthSetting extends Model
{
    /**
     * The database connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'authentication';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return $setting->value;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, ?string $description = null): void
    {
        $setting = static::firstOrNew(['key' => $key]);
        
        // Store value as string
        $setting->value = is_string($value) ? $value : json_encode($value);

        if ($description !== null) {
            $setting->description = $description;
        }

        $setting->save();
    }

    /**
     * Check if a boolean setting is enabled.
     */
    public static function isEnabled(string $key, bool $default = false): bool
    {
        return (bool) static::get($key, $default);
    }

    /**
     * Check if system is in maintenance mode.
     * system_code value 503 means maintenance mode.
     */
    public static function isMaintenanceMode(): bool
    {
        return static::get('system_code') === '503';
    }
}
