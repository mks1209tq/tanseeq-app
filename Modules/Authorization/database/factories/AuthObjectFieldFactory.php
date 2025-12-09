<?php

namespace Modules\Authorization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\AuthObjectField;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Authorization\Entities\AuthObjectField>
 */
class AuthObjectFieldFactory extends Factory
{
    protected $model = AuthObjectField::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auth_object_id' => AuthObject::factory(),
            'code' => strtoupper($this->faker->word()),
            'label' => $this->faker->words(2, true),
            'is_org_level' => $this->faker->boolean(30),
            'sort' => $this->faker->numberBetween(0, 100),
        ];
    }
}

