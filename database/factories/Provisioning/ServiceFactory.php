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
namespace Database\Factories\Provisioning;

use App\Models\Account\Customer;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Models\Store\Group;
use App\Models\Store\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Product>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        if (Group::count() == 0) {
            Group::factory()->create([
                'name' => 'Minecraft',
                'description' => 'Minecraft',
                'status' => 'active',
                'sort_order' => 1,
            ]);
        }
        $groupId = Group::first()->id;

        return [
            'name' => $this->faker->randomElement(['Minecraft Charbon', 'Minecraft Gold', 'Minecraft Diamant']),
            'customer_id' => Customer::first()->id,
            'type' => 'none',
            'billing' => 'monthly',
            'server_id' => Server::factory()->create()->id,
            'product_id' => Product::factory(['group_id' => $groupId])->create()->id,
            'invoice_id' => null,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addMonths(3),
            'data' => json_encode(['currency' => 'EUR', 'billing' => 'quarterly']),
        ];
    }
}
