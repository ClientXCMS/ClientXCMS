<?php

namespace Tests\Feature\Console;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDeliveryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsupported_paid_items_make_command_fail_without_stopping_the_batch(): void
    {
        Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now()->subHour(),
        ]);
        $first = InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'type' => 'unsupported-a']);
        $second = InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'type' => 'unsupported-b']);

        $this->artisan('invoices:delivery')
            ->expectsOutputToContain("Service delivery failed for invoice item {$first->id}")
            ->expectsOutputToContain("Service delivery failed for invoice item {$second->id}")
            ->assertExitCode(Command::FAILURE);
    }

    public function test_recent_renewal_is_skipped_without_failure(): void
    {
        Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now(),
        ]);
        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'type' => 'renewal']);

        $this->artisan('invoices:delivery')
            ->expectsOutputToContain("Skipping invoice item {$item->id}")
            ->assertExitCode(Command::SUCCESS);
    }
}
