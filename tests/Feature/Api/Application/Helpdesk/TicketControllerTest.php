<?php

namespace Tests\Feature\Api\Application\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportTicket;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    const API_URL = 'api/application/tickets';

    const ABILITY_INDEX = 'tickets:index';

    const ABILITY_STORE = 'tickets:store';

    const ABILITY_SHOW = 'tickets:show';

    const ABILITY_UPDATE = 'tickets:update';

    const ABILITY_DELETE = 'tickets:delete';

    const ABILITY_REPLY = 'tickets:reply';

    const ABILITY_CLOSE = 'tickets:close';

    const ABILITY_REOPEN = 'tickets:reopen';

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a customer that the TicketFactory depends on
        Customer::factory()->create();
    }

    public function test_api_application_ticket_index(): void
    {
        SupportTicket::factory(5)->create();
        $response = $this->performAction('GET', self::API_URL, [self::ABILITY_INDEX]);
        $response->assertStatus(200);
    }

    public function test_api_application_ticket_filter_by_status(): void
    {
        SupportTicket::factory()->create(['status' => SupportTicket::STATUS_OPEN]);
        SupportTicket::factory()->create(['status' => SupportTicket::STATUS_CLOSED]);

        $response = $this->performAction('GET', self::API_URL.'?filter[status]=open', [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_api_application_ticket_filter_by_priority(): void
    {
        SupportTicket::factory()->create(['priority' => 'high']);
        SupportTicket::factory()->create(['priority' => 'low']);

        $response = $this->performAction('GET', self::API_URL.'?filter[priority]=high', [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_api_application_ticket_sort(): void
    {
        SupportTicket::factory(5)->create();
        $lastTicket = SupportTicket::orderBy('id', 'desc')->first();
        $response = $this->performAction('GET', self::API_URL.'?sort=-id', [self::ABILITY_INDEX]);
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
        $this->assertEquals($lastTicket->id, $response->json('data.0.id'));
    }

    public function test_api_application_ticket_store(): void
    {
        $this->seed(StoreSeeder::class);
        $department = SupportDepartment::factory()->create();
        $customer = Customer::factory()->create();

        $response = $this->performAction('POST', self::API_URL, [self::ABILITY_STORE], [
            'department_id' => $department->id,
            'customer_id' => $customer->id,
            'subject' => 'Test ticket subject',
            'priority' => 'medium',
            'content' => 'This is a test ticket content with more than 5 characters',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('support_tickets', [
            'subject' => 'Test ticket subject',
            'customer_id' => $customer->id,
        ]);
    }

    public function test_api_application_ticket_show(): void
    {
        $ticket = SupportTicket::factory()->create();
        $response = $this->performAction('GET', self::API_URL.'/'.$ticket->id, [self::ABILITY_SHOW]);
        $response->assertStatus(200);
    }

    public function test_api_application_ticket_update(): void
    {
        $ticket = SupportTicket::factory()->create();
        $department = SupportDepartment::factory()->create();

        $response = $this->performAction('POST', self::API_URL.'/'.$ticket->id, [self::ABILITY_UPDATE], [
            'department_id' => $department->id,
            'subject' => 'Updated subject',
            'priority' => 'high',
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['subject' => 'Updated subject']);
    }

    public function test_api_application_ticket_delete_open_ticket(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => SupportTicket::STATUS_OPEN]);

        $response = $this->performAction('DELETE', self::API_URL.'/'.$ticket->id, [self::ABILITY_DELETE]);
        $response->assertStatus(200);

        // Open ticket should be closed, not deleted
        $ticket->refresh();
        $this->assertEquals(SupportTicket::STATUS_CLOSED, $ticket->status);
    }

    public function test_api_application_ticket_delete_closed_ticket(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => SupportTicket::STATUS_CLOSED]);

        $response = $this->performAction('DELETE', self::API_URL.'/'.$ticket->id, [self::ABILITY_DELETE]);
        $response->assertStatus(200);
        $this->assertSoftDeleted('support_tickets', ['id' => $ticket->id]);
    }

    public function test_api_application_ticket_reply(): void
    {
        $ticket = SupportTicket::factory()->create();

        $response = $this->performAction('POST', self::API_URL.'/'.$ticket->id.'/reply', [self::ABILITY_REPLY], [
            'content' => 'This is a reply message to the ticket',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('support_messages', [
            'ticket_id' => $ticket->id,
            'message' => 'This is a reply message to the ticket',
        ]);
    }

    public function test_api_application_ticket_close(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => SupportTicket::STATUS_OPEN]);

        $response = $this->performAction('POST', self::API_URL.'/'.$ticket->id.'/close', [self::ABILITY_CLOSE], [
            'reason' => 'Issue resolved',
        ]);
        $response->assertStatus(200);

        $ticket->refresh();
        $this->assertEquals(SupportTicket::STATUS_CLOSED, $ticket->status);
        $this->assertEquals('Issue resolved', $ticket->close_reason);
    }

    public function test_api_application_ticket_close_already_closed(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => SupportTicket::STATUS_CLOSED]);

        $response = $this->performAction('POST', self::API_URL.'/'.$ticket->id.'/close', [self::ABILITY_CLOSE]);
        $response->assertStatus(400);
    }

    public function test_api_application_ticket_reopen(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => SupportTicket::STATUS_CLOSED]);

        $response = $this->performAction('POST', self::API_URL.'/'.$ticket->id.'/reopen', [self::ABILITY_REOPEN]);
        $response->assertStatus(200);

        $ticket->refresh();
        $this->assertEquals(SupportTicket::STATUS_OPEN, $ticket->status);
    }

    public function test_api_application_ticket_reopen_not_closed(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => SupportTicket::STATUS_OPEN]);

        $response = $this->performAction('POST', self::API_URL.'/'.$ticket->id.'/reopen', [self::ABILITY_REOPEN]);
        $response->assertStatus(400);
    }
}
