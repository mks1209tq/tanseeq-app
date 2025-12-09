<?php

namespace Modules\Authorization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Authorization\Entities\AuthObject;
use Modules\Authorization\Entities\Role;
use Modules\Authorization\Entities\RoleAuthorization;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Authorization\Entities\RoleAuthorization>
 */
class RoleAuthorizationFactory extends Factory
{
    protected $model = RoleAuthorization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => Role::factory(),
            'auth_object_id' => AuthObject::factory(),
            'label' => $this->faker->sentence(),
        ];
    }
}

