<?php

namespace Modules\ConfigTransports\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ConfigTransports\Entities\TransportRequest;
use Modules\Authentication\Entities\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\ConfigTransports\Entities\TransportRequest>
 */
class TransportRequestFactory extends Factory
{
    protected $model = TransportRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = $this->faker->unique()->numberBetween(900001, 999999);
        $envPrefix = strtoupper(config('system.environment_role', 'dev'));

        return [
            'number' => $envPrefix.'K'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'type' => $this->faker->randomElement(['security', 'config', 'master_data', 'mixed']),
            'status' => 'open',
            'source_environment' => config('system.environment_role', 'dev'),
            'target_environments' => ['qa', 'prod'],
            'description' => $this->faker->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the transport request is released.
     */
    public function released(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'released',
            'released_by' => $attributes['created_by'],
            'released_at' => now(),
        ]);
    }

    /**
     * Indicate that the transport request is exported.
     */
    public function exported(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'exported',
            'released_by' => $attributes['created_by'],
            'released_at' => now()->subHour(),
        ]);
    }
}

