<?php

namespace Modules\Authentication\Entities;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Modules\Authorization\Traits\HasRolesAndAuthorizations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @extends \Illuminate\Database\Eloquent\Model
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Authorization\Entities\Role> $roles
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Modules\Authentication\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRolesAndAuthorizations;

    /**
     * The database connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'authentication';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

