<?php

namespace Tests\Feature\Webhook;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportDepartment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class HelpdeskInboundEmailControllerTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    public function test_it_creates_a_ticket_for_first_inbound_email_from_existing_customer(): void
    {
        $customer = Customer::factory()->create(['email' => 'client@example.test']);
        $department = SupportDepartment::create([
            'name' => 'Support',
            'description' => 'Main support',
        ]);

        $response = $this->withHeader('X-Helpdesk-Webhook-Token', (string) setting('helpdesk_inbound_webhook_token'))
            ->postJson('/api/client/webhooks/helpdesk/inbound-email', [
                'from' => 'Client Test <client@example.test>',
                'recipient' => 'support@clientxcms.test',
                'subject' => 'Problème de facturation',
                'stripped-text' => 'Bonjour, voici mon souci.',
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('support_tickets', [
            'customer_id' => $customer->id,
            'department_id' => $department->id,
            'subject' => 'Problème de facturation',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $this->assertDatabaseHas('support_messages', [
            'customer_id' => $customer->id,
            'message' => 'Bonjour, voici mon souci.',
        ]);
    }

    public function test_it_rejects_first_inbound_email_when_sender_is_not_a_customer(): void
    {
        SupportDepartment::create([
            'name' => 'Support',
            'description' => 'Main support',
        ]);

        $response = $this->withHeader('X-Helpdesk-Webhook-Token', (string) setting('helpdesk_inbound_webhook_token'))
            ->postJson('/api/client/webhooks/helpdesk/inbound-email', [
                'from' => 'ghost@example.test',
                'recipient' => 'support@clientxcms.test',
                'subject' => 'Aide',
                'stripped-text' => 'Bonjour',
            ]);

        $response->assertStatus(422)->assertJson(['error' => 'sender_not_client']);
        $this->assertDatabaseCount('support_tickets', 0);
    }
}
