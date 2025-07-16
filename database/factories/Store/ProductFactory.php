<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
namespace Database\Factories\Store;

use App\Models\Store\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store\Product>
 */
class ProductFactory extends Factory
{
    protected $model = \App\Models\Store\Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        if (! Group::exists()) {
            Group::factory()->create();
        }
        $first = Group::first()->id;

        return [
            'name' => $this->faker->word,
            'group_id' => $first,
            'status' => $this->faker->randomElement(['active', 'hidden']),
            'description' => $this->faker->sentence,
            'sort_order' => $this->faker->randomDigit,
            'type' => 'pterodactyl',
            'stock' => $this->faker->randomDigit,
        ];
    }
}
