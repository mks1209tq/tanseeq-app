<?php

namespace Modules\Todo\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Todo\Entities\Todo;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Todo\Entities\Todo>
 */
class TodoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = \Modules\Todo\Entities\Todo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $priorities = ['low', 'medium', 'high'];
        $hasDueDate = $this->faker->boolean(70);

        return [
            'user_id' => \Modules\Authentication\Entities\User::factory(),
            'title' => $this->faker->sentence(3, 8),
            'description' => $this->faker->optional(0.7)->paragraph(2, 5),
            'completed' => $this->faker->boolean(30),
            'priority' => $this->faker->randomElement($priorities),
            'due_date' => $hasDueDate ? $this->faker->dateTimeBetween('-1 week', '+1 month')->format('Y-m-d') : null,
        ];
    }

    /**
     * Indicate that the todo is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed' => true,
        ]);
    }

    /**
     * Indicate that the todo is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed' => false,
        ]);
    }

    /**
     * Indicate that the todo has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the todo has medium priority.
     */
    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'medium',
        ]);
    }

    /**
     * Indicate that the todo has low priority.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'low',
        ]);
    }

    /**
     * Indicate that the todo is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('-2 weeks', '-1 day')->format('Y-m-d'),
            'completed' => false,
        ]);
    }

    /**
     * Indicate that the todo is due today.
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the todo is due in the future.
     */
    public function dueFuture(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('+1 day', '+1 month')->format('Y-m-d'),
        ]);
    }
}
