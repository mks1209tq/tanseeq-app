<?php

namespace App\DTOs;

class UserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $emailVerifiedAt = null,
        public readonly array $roles = [],
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            email: $data['email'],
            emailVerifiedAt: $data['email_verified_at'] ?? null,
            roles: $data['roles'] ?? [],
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->emailVerifiedAt,
            'roles' => $this->roles,
        ];
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string|array $roles): bool
    {
        $roleNames = is_array($roles) ? $roles : [$roles];

        return ! empty(array_intersect($roleNames, $this->roles));
    }

    /**
     * Check if user is super-admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(['SuperAdmin', 'super-admin', 'SUPER_ADMIN']);
    }
}

