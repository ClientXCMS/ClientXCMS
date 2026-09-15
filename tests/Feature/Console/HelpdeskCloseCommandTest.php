<?php

namespace Tests\Feature\Console;

use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use App\Models\Helpdesk\SupportTicket;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HelpdeskCloseCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_only_old_open_or_answered_tickets_are_closed(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');
        Event::fake();
        Setting::updateSettings(['helpdesk_ticket_auto_close_days' => 7], null, false);
        $customer = Customer::factory()->create();
        $departmentId = DB::table('support_departments')->insertGetId([
            'name' => 'Support',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $old = $this->ticket($customer->id, $departmentId, 'open', now()->subDays(8));
        $recent = $this->ticket($customer->id, $departmentId, 'open', now()->subDays(6));
        $closed = $this->ticket($customer->id, $departmentId, 'closed', now()->subDays(8));

        $this->artisan('clientxcms:helpdesk-close')
            ->expectsOutputToContain("Ticket #{$old->id} closed.")
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame('closed', $old->fresh()->status);
        $this->assertSame('open', $recent->fresh()->status);
        $this->assertSame('closed', $closed->fresh()->status);
    }

    private function ticket(int $customerId, int $departmentId, string $status, Carbon $updatedAt): SupportTicket
    {
        $ticket = new SupportTicket;
        $ticket->forceFill([
            'department_id' => $departmentId,
            'customer_id' => $customerId,
            'subject' => 'Cron test',
            'status' => $status,
            'priority' => 'medium',
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ])->saveQuietly();

        return $ticket;
    }
}
