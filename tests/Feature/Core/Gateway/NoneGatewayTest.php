<?php

namespace Tests\Feature\Core\Gateway;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Gateway\GatewayTestHelpers;
use Tests\TestCase;

class NoneGatewayTest extends TestCase
{
    use GatewayTestHelpers;
    use RefreshDatabase;

    public function test_return_keeps_non_zero_invoice_pending(): void
    {
        $this->createGateway('none', 'hidden');
        $customer = Customer::factory()->create();
        $invoice = $this->createInvoice(['total' => 50, 'subtotal' => 50, 'paymethod' => 'none'], $customer);

        $this->actingAs($customer, 'web')->get("/gateways/{$invoice->uuid}/none/return")
            ->assertRedirect(route('front.invoices.show', $invoice))
            ->assertSessionHas('error', __('store.checkout.wrong_payment'));

        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_return_keeps_cent_invoice_pending(): void
    {
        $this->createGateway('none', 'hidden');
        $customer = Customer::factory()->create();
        $invoice = $this->createInvoice(['total' => 0.01, 'subtotal' => 0.01, 'paymethod' => 'none'], $customer);

        $this->actingAs($customer, 'web')->get("/gateways/{$invoice->uuid}/none/return");

        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_return_pays_zero_invoice(): void
    {
        $this->createGateway('none', 'hidden');
        $customer = Customer::factory()->create();
        $invoice = $this->createInvoice(['total' => 0, 'subtotal' => 0, 'paymethod' => 'none'], $customer);

        $this->actingAs($customer, 'web')->get("/gateways/{$invoice->uuid}/none/return")
            ->assertRedirect(route('front.invoices.show', $invoice));

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_customer_cannot_select_none_gateway_on_non_zero_invoice(): void
    {
        $this->createGateway('none', 'hidden');
        $customer = Customer::factory()->create();
        $invoice = $this->createInvoice(['total' => 50, 'subtotal' => 50, 'paymethod' => 'stripe'], $customer);

        $this->actingAs($customer, 'web')->get("/client/invoices/{$invoice->id}/pay/none")
            ->assertSessionHas('error', __('store.checkout.gateway_not_found'));

        $fresh = $invoice->fresh();
        $this->assertSame(Invoice::STATUS_PENDING, $fresh->status);
        $this->assertSame('stripe', $fresh->paymethod);
    }
}
