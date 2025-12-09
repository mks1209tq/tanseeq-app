<?php

namespace Modules\Authorization\Entities;

use Illuminate\Database\Eloquent\Model;

class PrivilegedActivityLog extends Model
{
    protected $connection = 'authorization';

    protected $fillable = [
        'user_id',
        'role_type',
        'auth_object_code',
        'activity_code',
        'required_fields',
        'route_name',
        'request_path',
        'request_method',
        'request_data',
        'client_ip',
        'user_agent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'required_fields' => 'array',
            'request_data' => 'array',
        ];
    }

    /**
     * Get the user that performed the action.
     */
    public function user()
    {
        return $this->belongsTo(\Modules\Authentication\Entities\User::class, 'user_id');
    }
}

