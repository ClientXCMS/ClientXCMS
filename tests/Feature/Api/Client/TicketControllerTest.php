<?php

namespace Tests\Feature\Api\Client;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    private function authenticatedCustomer(): array
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('client-api', ['*']);

        return [$customer, $token->plainTextToken];
    }

    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];
    }

    private function createDepartment(): SupportDepartment
    {
        return SupportDepartment::create([
            'name' => 'Test Department',
            'description' => 'A test department',
        ]);
    }

    private function createTicket(Customer $customer, SupportDepartment $department, string $status = SupportTicket::STATUS_OPEN): SupportTicket
    {
        $ticket = new SupportTicket;
        $ticket->customer_id = $customer->id;
        $ticket->department_id = $department->id;
        $ticket->subject = 'Test Ticket';
        $ticket->priority = 'medium';
        $ticket->status = $status;
        $ticket->save();
        $ticket->addMessage('Test message content', $customer->id);

        return $ticket;
    }

    public function test_customer_can_list_tickets(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $department = $this->createDepartment();
        $this->createTicket($customer, $department);
        $this->createTicket($customer, $department);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/tickets');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'uuid', 'subject', 'status', 'priority', 'department', 'created_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'filters',
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_customer_can_filter_tickets_by_status(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $department = $this->createDepartment();
        $this->createTicket($customer, $department, SupportTicket::STATUS_OPEN);
        $this->createTicket($customer, $department, SupportTicket::STATUS_CLOSED);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/tickets?filter='.SupportTicket::STATUS_OPEN);

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_customer_cannot_see_other_customer_tickets(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $otherCustomer = Customer::factory()->create();
        $department = $this->createDepartment();
        $this->createTicket($otherCustomer, $department);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/tickets');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_customer_can_view_ticket_details(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $department = $this->createDepartment();
        $ticket = $this->createTicket($customer, $department);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/tickets/'.$ticket->id);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'subject',
                    'status',
                    'priority',
                    'department',
                    'messages',
                    'attachments',
                    'created_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $ticket->id,
                    'subject' => 'Test Ticket',
                ],
            ]);
    }

    public function test_customer_cannot_view_other_customer_ticket(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $otherCustomer = Customer::factory()->create();
        $department = $this->createDepartment();
        $ticket = $this->createTicket($otherCustomer, $department);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/tickets/'.$ticket->id);

        $response->assertNotFound();
    }

    public function test_customer_can_create_ticket(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $department = $this->createDepartment();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/client/tickets', [
                'department_id' => $department->id,
                'subject' => 'New Support Request',
                'content' => 'I need help with something.',
                'priority' => 'high',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'uuid', 'subject', 'status', 'priority'],
            ]);

        $this->assertDatabaseHas('support_tickets', [
            'customer_id' => $customer->id,
            'subject' => 'New Support Request',
            'priority' => 'high',
        ]);
    }

    public function test_customer_can_reply_to_ticket(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $department = $this->createDepartment();
        $ticket = $this->createTicket($customer, $department);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/client/tickets/'.$ticket->id.'/reply', [
                'content' => 'This is my reply message.',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => __('helpdesk.support.ticket_replied'),
            ]);

        $this->assertDatabaseHas('support_messages', [
            'ticket_id' => $ticket->id,
            'message' => 'This is my reply message.',
        ]);
    }

    public function test_customer_cannot_reply_to_closed_ticket(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $department = $this->createDepartment();
        $ticket = $this->createTicket($customer, $department, SupportTicket::STATUS_CLOSED);
        $ticket->closed_at = now();
        $ticket->save();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/client/tickets/'.$ticket->id.'/reply', [
                'content' => 'Trying to reply to closed ticket.',
            ]);

        $response->assertBadRequest();
    }

    public function test_customer_can_close_ticket(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $department = $this->createDepartment();
        $ticket = $this->createTicket($customer, $department);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/client/tickets/'.$ticket->id.'/close');

        $response->assertOk()
            ->assertJson([
                'message' => __('helpdesk.support.ticket_closed'),
            ]);

        $ticket->refresh();
        $this->assertEquals(SupportTicket::STATUS_CLOSED, $ticket->status);
    }

    public function test_customer_can_reopen_recently_closed_ticket(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $department = $this->createDepartment();
        $ticket = $this->createTicket($customer, $department, SupportTicket::STATUS_CLOSED);
        $ticket->closed_at = now()->subDay();
        $ticket->save();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/client/tickets/'.$ticket->id.'/reopen');

        $response->assertOk()
            ->assertJson([
                'message' => __('helpdesk.support.ticket_reopened'),
            ]);

        $ticket->refresh();
        $this->assertEquals(SupportTicket::STATUS_OPEN, $ticket->status);
    }

    public function test_customer_can_view_departments(): void
    {
        [$customer, $token] = $this->authenticatedCustomer();
        $this->createDepartment();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/client/tickets/departments');

        $response->assertOk()
            ->assertJsonStructure([
                'departments' => [
                    '*' => ['id', 'name', 'description'],
                ],
                'priorities',
            ]);
    }
}
