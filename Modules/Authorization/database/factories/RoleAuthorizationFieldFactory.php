<?php

namespace Modules\Authorization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Authorization\Entities\RoleAuthorization;
use Modules\Authorization\Entities\RoleAuthorizationField;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Authorization\Entities\RoleAuthorizationField>
 */
class RoleAuthorizationFieldFactory extends Factory
{
    protected $model = RoleAuthorizationField::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_authorization_id' => RoleAuthorization::factory(),
            'field_code' => strtoupper($this->faker->word()),
            'operator' => $this->faker->randomElement(['*', '=', 'in', 'between']),
            'value_from' => $this->faker->word(),
            'value_to' => $this->faker->optional()->word(),
        ];
    }
}

