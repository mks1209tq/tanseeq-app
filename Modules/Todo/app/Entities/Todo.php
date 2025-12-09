<?php

namespace Modules\Todo\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Todo\Database\Factories\TodoFactory;

class Todo extends Model
{
    use HasFactory;

    /**
     * The database connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'todo';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'completed',
        'priority',
        'due_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'due_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the todo.
     * 
     * Note: In microservice mode, this uses the AuthenticationServiceClient
     * instead of a direct Eloquent relationship.
     */
    public function getUserAttribute()
    {
        $authService = app(\App\Contracts\Services\AuthenticationServiceInterface::class);
        
        return $authService->getUserById($this->user_id);
    }

    /**
     * Get the user ID (for backward compatibility and queries).
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * Scope a query to only include completed todos.
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Scope a query to only include pending todos.
     */
    public function scopePending($query)
    {
        return $query->where('completed', false);
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return TodoFactory::new();
    }
}
