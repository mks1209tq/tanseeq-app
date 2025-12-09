<?php

namespace Modules\Authorization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Authorization\Entities\AuthObject;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Authorization\Entities\AuthObject>
 */
class AuthObjectFactory extends Factory
{
    protected $model = AuthObject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->words(3, true)),
            'description' => $this->faker->sentence(),
        ];
    }
}

