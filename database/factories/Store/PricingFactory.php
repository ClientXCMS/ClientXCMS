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
