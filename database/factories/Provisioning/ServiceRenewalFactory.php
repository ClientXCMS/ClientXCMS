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
