<?php

namespace Tests\Unit\Services\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportMessage;
use App\Models\Helpdesk\SupportTicket;
use App\Services\Helpdesk\SlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlaServiceTest extends TestCase
{
    use RefreshDatabase;

    private SlaService $sla;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sla = new SlaService;
    }

    public function test_apply_on_create_does_nothing_without_sla_settings(): void
    {
        $department = SupportDepartment::factory()->create();
        $customer = Customer::factory()->create();
        $ticket = SupportTicket::factory()->create([
            'department_id' => $department->id,
            'customer_id' => $customer->id,
        ]);

        $this->sla->applyOnCreate($ticket->fresh());

        $ticket->refresh();
        $this->assertNull($ticket->first_response_due_at);
        $this->assertNull($ticket->resolution_due_at);
    }

    public function test_apply_on_create_sets_due_dates_based_on_department(): void
    {
        $department = SupportDepartment::factory()->create();
        $department->forceFill([
            'sla_first_response_minutes' => 60,
            'sla_resolution_minutes' => 24 * 60,
        ])->save();

        $customer = Customer::factory()->create();
        $ticket = SupportTicket::factory()->create([
            'department_id' => $department->id,
            'customer_id' => $customer->id,
        ]);

        Carbon::setTestNow($ticket->created_at);
        $this->sla->applyOnCreate($ticket->fresh());
        $ticket->refresh();
        Carbon::setTestNow();

        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertNotNull($ticket->resolution_due_at);
        // Allow a 1-second drift between created_at and the assertion.
        $this->assertLessThanOrEqual(
            1,
            abs($ticket->first_response_due_at->diffInSeconds($ticket->created_at->copy()->addMinutes(60)))
        );
    }

    public function test_record_first_response_only_fires_once(): void
    {
        $customer = Customer::factory()->create();
        $ticket = SupportTicket::factory()->create(['customer_id' => $customer->id]);

        $staffMessage = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'admin_id' => 1,
            'message' => 'Hi',
        ]);

        $this->sla->recordFirstResponse($ticket, $staffMessage);
        $ticket->refresh();
        $this->assertNotNull($ticket->first_response_at);
        $first = $ticket->first_response_at;

        // A second call should be a no-op.
        $later = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'admin_id' => 1,
            'message' => 'and another',
        ]);
        $this->sla->recordFirstResponse($ticket, $later);
        $this->assertSame($first->toIso8601String(), $ticket->fresh()->first_response_at->toIso8601String());
    }

    public function test_record_first_response_ignores_customer_messages(): void
    {
        $customer = Customer::factory()->create();
        $ticket = SupportTicket::factory()->create(['customer_id' => $customer->id]);

        $customerMessage = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'customer_id' => $customer->id,
            'message' => 'Hi staff',
        ]);

        $this->sla->recordFirstResponse($ticket, $customerMessage);
        $this->assertNull($ticket->fresh()->first_response_at);
    }

    public function test_fresh_breaches_returns_only_open_tickets_past_their_due_date(): void
    {
        Carbon::setTestNow('2026-05-23 12:00:00');

        $customer = Customer::factory()->create();

        $breached = SupportTicket::factory()->create([
            'customer_id' => $customer->id,
            'status' => SupportTicket::STATUS_OPEN,
            'first_response_due_at' => Carbon::now()->subMinutes(30),
        ]);
        $onTime = SupportTicket::factory()->create([
            'customer_id' => $customer->id,
            'status' => SupportTicket::STATUS_OPEN,
            'first_response_due_at' => Carbon::now()->addMinutes(30),
        ]);
        $closed = SupportTicket::factory()->create([
            'customer_id' => $customer->id,
            'status' => SupportTicket::STATUS_CLOSED,
            'first_response_due_at' => Carbon::now()->subMinutes(30),
        ]);

        $ids = $this->sla->freshBreaches()->pluck('id')->toArray();

        $this->assertContains($breached->id, $ids);
        $this->assertNotContains($onTime->id, $ids);
        $this->assertNotContains($closed->id, $ids);

        Carbon::setTestNow();
    }
}
