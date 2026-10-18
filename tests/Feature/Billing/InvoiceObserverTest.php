<?php

namespace Tests\Feature\Billing;

use App\Models\Billing\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_fees_can_be_recorded_on_an_issued_invoice(): void
    {
        $customer = $this->createCustomerModel();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'issued_at' => now(),
            'fees' => 0,
        ]);

        $invoice->update([
            'external_id' => 'pi_test',
            'fees' => 1.25,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'external_id' => 'pi_test',
            'fees' => 1.25,
        ]);
    }
}
