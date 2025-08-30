<?php

namespace Database\Factories\Provisioning;

use App\Models\Billing\InvoiceItem;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceRenewals;
use App\Models\Store\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Product>
 */
class ServiceRenewalFactory extends Factory
{
    protected $model = ServiceRenewals::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $service = Service::factory()->create();
        $invoice = InvoiceItem::factory()->create()->invoice_id;

        return [
            'service_id' => $service->id,
            'invoice_id' => $invoice,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'renewed_at' => Carbon::now(),
            'first_period' => true,
            'next_billing_on' => Carbon::now()->addMonths(6),
        ];
    }
}
