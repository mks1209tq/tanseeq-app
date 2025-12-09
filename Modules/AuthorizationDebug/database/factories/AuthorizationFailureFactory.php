<?php

namespace Modules\AuthorizationDebug\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AuthorizationDebug\Entities\AuthorizationFailure;
use Modules\Authentication\Entities\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\AuthorizationDebug\Entities\AuthorizationFailure>
 */
class AuthorizationFailureFactory extends Factory
{
    protected $model = AuthorizationFailure::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'auth_object_code' => strtoupper($this->faker->words(3, true)),
            'required_fields' => [
                'ACTVT' => $this->faker->randomElement(['01', '02', '03', '04', '05']),
                'COMP_CODE' => $this->faker->numerify('####'),
            ],
            'summary' => [
                'ACTVT' => [
                    'rules' => [
                        [
                            'operator' => 'in',
                            'values' => ['01', '02', '03'],
                        ],
                    ],
                ],
                'COMP_CODE' => [
                    'rules' => [
                        [
                            'operator' => 'in',
                            'values' => ['1000', '2000', '3000'],
                        ],
                    ],
                ],
            ],
            'is_allowed' => $this->faker->boolean(),
            'route_name' => $this->faker->optional()->word().'.'.$this->faker->word(),
            'request_path' => '/'.$this->faker->word(),
            'request_method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']),
            'client_ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Indicate that the authorization check was allowed.
     */
    public function allowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_allowed' => true,
        ]);
    }

    /**
     * Indicate that the authorization check was denied.
     */
    public function denied(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_allowed' => false,
        ]);
    }
}

