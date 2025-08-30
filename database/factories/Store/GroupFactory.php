<?php

namespace Database\Factories\Store;

use App\Models\Store\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store\Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'slug' => $this->faker->slug.'-'.$this->faker->randomNumber(4),
            'status' => $this->faker->randomElement(['active', 'hidden']),
            'description' => $this->faker->sentence,
            'sort_order' => $this->faker->randomDigit,
            'pinned' => $this->faker->boolean,
            'image' => $this->faker->randomElement(['groups/minecraft.webp', 'groups/VPS.png']),
            'parent_id' => null,
        ];
    }
}
