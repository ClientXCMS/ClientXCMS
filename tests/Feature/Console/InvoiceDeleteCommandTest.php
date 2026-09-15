<?php

namespace Tests\Feature\Console;

use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use App\Models\Billing\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDeleteCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_delete_mode_removes_only_old_unlocked_invoices(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        Customer::factory()->create();
        Setting::updateSettings([
            'remove_pending_invoice' => 7,
            'remove_pending_invoice_type' => 'delete',
        ], null, false);

        $old = Invoice::factory()->create(['created_at' => now()->subDays(8)]);
        $recent = Invoice::factory()->create(['created_at' => now()->subDays(6)]);

        $this->artisan('clientxcms:invoice-delete')
            ->expectsOutputToContain("Invoice #{$old->id} deleted.")
            ->assertExitCode(Command::SUCCESS);

        $this->assertSoftDeleted($old);
        $this->assertModelExists($recent);
    }

    public function test_locked_invoice_is_kept_and_makes_the_command_fail(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        Customer::factory()->create();
        Setting::updateSettings([
            'remove_pending_invoice' => 7,
            'remove_pending_invoice_type' => 'delete',
        ], null, false);
        $invoice = Invoice::factory()->create(['created_at' => now()->subDays(8)]);
        $invoice->forceFill(['issued_at' => now()->subDays(7)])->saveQuietly();

        $this->artisan('clientxcms:invoice-delete')
            ->expectsOutputToContain("Invoice #{$invoice->id} could not be processed")
            ->assertExitCode(Command::FAILURE);

        $this->assertModelExists($invoice);
    }

    public function test_cancel_mode_cancels_old_invoice_without_deleting_it(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        Customer::factory()->create();
        Setting::updateSettings([
            'remove_pending_invoice' => 7,
            'remove_pending_invoice_type' => 'cancel',
        ], null, false);
        $invoice = Invoice::factory()->create(['created_at' => now()->subDays(8)]);

        $this->artisan('clientxcms:invoice-delete')->assertExitCode(Command::SUCCESS);

        $this->assertSame(Invoice::STATUS_CANCELLED, $invoice->fresh()->status);
    }
}
