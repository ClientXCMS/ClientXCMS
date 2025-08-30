<?php

namespace Database\Factories\Store;

use App\Models\Store\Pricing;
use App\Models\Store\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store\Pricing>
 */
class PricingFactory extends Factory
{
    protected $model = Pricing::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'related_id' => Product::factory(),
            'related_type' => 'product',
            'onetime' => 1,
            'monthly' => 1,
            'quarterly' => 3,
            'semiannually' => 6,
            'setup_onetime' => 0,
            'setup_monthly' => 0,
            'setup_quarterly' => 0,
            'setup_semiannually' => 0,
        ];
    }
}
