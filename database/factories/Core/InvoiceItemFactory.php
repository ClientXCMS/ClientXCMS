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
namespace Database\Factories\Core;

use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Store\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        if (! Product::exists()) {
            Product::factory()->create();
        }

        return [
            'invoice_id' => Invoice::factory()->create()->id,
            'description' => $this->faker->text(),
            'name' => $this->faker->randomElement(['Minecraft Charbon', 'Minecraft Gold', 'Minecraft Diamant']),
            'quantity' => 1,
            'unit_price_ht' => 1,
            'unit_setup_ht' => 0,
            'unit_price_ttc' => 1,
            'unit_setup_ttc' => 0,
            'type' => 'service',
            'related_id' => Product::first()->id,
            'data' => json_encode(['billing' => 'monthly', 'currency' => 'EUR']),
        ];
    }

    public function modelName()
    {
        return InvoiceItem::class;
    }
}
