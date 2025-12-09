<?php

namespace Modules\Authorization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Authorization\Entities\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Authorization\Entities\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'description' => $this->faker->sentence(),
        ];
    }
}

