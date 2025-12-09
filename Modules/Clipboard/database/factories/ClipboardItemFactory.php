<?php

namespace Modules\Clipboard\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clipboard\Entities\ClipboardItem;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Clipboard\Entities\ClipboardItem>
 */
class ClipboardItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ClipboardItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'title' => $this->faker->sentence(3),
            'content' => $this->faker->text(200),
            'type' => 'text',
            'order' => 0,
        ];
    }
}

