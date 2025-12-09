<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        $subdomain = str()->slug($name);

        return [
            'name' => $name,
            'domain' => $this->faker->optional()->domainName(),
            'subdomain' => $subdomain,
            'database_prefix' => 'tenant_'.time().'_'.$this->faker->unique()->randomNumber(4),
            'status' => 'active',
            'plan' => $this->faker->randomElement(['basic', 'premium', 'enterprise']),
            'max_users' => $this->faker->numberBetween(10, 1000),
            'expires_at' => null,
            'settings' => null,
        ];
    }

    /**
     * Indicate that the tenant is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the tenant is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the tenant is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }
}
