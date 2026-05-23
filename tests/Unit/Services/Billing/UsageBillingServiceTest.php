<?php

namespace Tests\Unit\Services\Billing;

use App\Models\Account\Customer;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceUsageMetric;
use App\Models\Store\Product;
use App\Models\Store\ProductMeteredRate;
use App\Services\Billing\UsageBillingService;
use Database\Seeders\GatewaySeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UsageBillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private UsageBillingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
        $this->service = new UsageBillingService;
    }

    public function test_record_metric_persists_sample(): void
    {
        $customer = Customer::factory()->create();
        $service = $this->createServiceModel($customer->id);

        $sample = $this->service->recordMetric($service, 'cpu_cores', 2.5);

        $this->assertDatabaseHas('service_usage_metrics', [
            'id' => $sample->id,
            'metric_key' => 'cpu_cores',
            'value' => 2.5,
        ]);
    }

    public function test_peak_for_returns_max_within_window(): void
    {
        $customer = Customer::factory()->create();
        $service = $this->createServiceModel($customer->id);

        Carbon::setTestNow('2026-05-15 12:00:00');
        $this->service->recordMetric($service, 'cpu_cores', 1.0, Carbon::parse('2026-05-10 03:00:00'));
        $this->service->recordMetric($service, 'cpu_cores', 5.0, Carbon::parse('2026-05-12 03:00:00'));
        $this->service->recordMetric($service, 'cpu_cores', 2.0, Carbon::parse('2026-05-14 03:00:00'));
        // Out of window — must not contribute to the peak.
        $this->service->recordMetric($service, 'cpu_cores', 99.0, Carbon::parse('2026-06-01 03:00:00'));

        $peak = $this->service->peakFor(
            $service,
            'cpu_cores',
            Carbon::parse('2026-05-01 00:00:00'),
            Carbon::parse('2026-05-31 23:59:59')
        );

        $this->assertSame(5.0, $peak);
        Carbon::setTestNow();
    }

    public function test_generate_monthly_invoice_only_bills_overage(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $service = $this->createServiceModel($customer->id);
        $service->product_id = $product->id;
        $service->save();

        ProductMeteredRate::create([
            'product_id' => $product->id,
            'metric_key' => 'cpu_cores',
            'label' => 'CPU core',
            'unit' => 'core',
            'unit_price' => 0.5,
            'included_quantity' => 2.0,
            'currency' => 'EUR',
        ]);

        $start = Carbon::parse('2026-05-01 00:00:00');
        $end = Carbon::parse('2026-05-31 23:59:59');

        // Peak = 4 → billable = 4 - 2 = 2 → charge = 2 * 0.5 = 1.0
        $this->service->recordMetric($service, 'cpu_cores', 4.0, Carbon::parse('2026-05-10 12:00:00'));

        $invoice = $this->service->generateMonthlyInvoice($customer->id, $start, $end);

        $this->assertNotNull($invoice);
        $this->assertEquals(1.0, (float) $invoice->subtotal);
        $this->assertSame(1, $invoice->items()->count());
    }

    public function test_generate_monthly_invoice_skips_customers_under_included_quantity(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $service = $this->createServiceModel($customer->id);
        $service->product_id = $product->id;
        $service->save();

        ProductMeteredRate::create([
            'product_id' => $product->id,
            'metric_key' => 'cpu_cores',
            'label' => 'CPU core',
            'unit_price' => 0.5,
            'included_quantity' => 10.0,
            'currency' => 'EUR',
        ]);

        $this->service->recordMetric($service, 'cpu_cores', 5.0, Carbon::parse('2026-05-10 12:00:00'));

        $invoice = $this->service->generateMonthlyInvoice(
            $customer->id,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31 23:59:59')
        );

        $this->assertNull($invoice);
    }
}
