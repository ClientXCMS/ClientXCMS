<?php

namespace Tests\Feature\Webhook;

use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use App\Models\Helpdesk\SupportDepartment;
use App\Services\Helpdesk\InboundEmailBridgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class HelpdeskInboundEmailControllerTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    public function test_it_creates_a_ticket_for_first_inbound_email_from_existing_customer(): void
    {
        Setting::updateSettings([
            'helpdesk_inbound_webhook_token' => 'inbound-token-123456',
        ]);

        $customer = Customer::factory()->create(['email' => 'client@example.test']);
        $department = SupportDepartment::create([
            'name' => 'Support',
            'description' => 'Main support',
        ]);

        $request = Request::create('/api/client/webhooks/helpdesk/inbound-email', 'POST', [
            'from' => 'Client Test <client@example.test>',
            'recipient' => 'support@clientxcms.test',
            'subject' => 'Problème de facturation',
            'stripped-text' => 'Bonjour, voici mon souci.',
        ], [], [], [
            'HTTP_X_HELPDESK_WEBHOOK_TOKEN' => 'inbound-token-123456',
        ]);

        $response = app(InboundEmailBridgeService::class)->handle($request);

        $this->assertSame(200, $response['status']);
        $this->assertSame(true, $response['payload']['success']);

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
        Setting::updateSettings([
            'helpdesk_inbound_webhook_token' => 'inbound-token-123456',
        ]);

        SupportDepartment::create([
            'name' => 'Support',
            'description' => 'Main support',
        ]);

        $request = Request::create('/api/client/webhooks/helpdesk/inbound-email', 'POST', [
            'from' => 'ghost@example.test',
            'recipient' => 'support@clientxcms.test',
            'subject' => 'Aide',
            'stripped-text' => 'Bonjour',
        ], [], [], [
            'HTTP_X_HELPDESK_WEBHOOK_TOKEN' => 'inbound-token-123456',
        ]);

        $response = app(InboundEmailBridgeService::class)->handle($request);

        $this->assertSame(422, $response['status']);
        $this->assertSame('sender_not_client', $response['payload']['error']);
        $this->assertDatabaseCount('support_tickets', 0);
    }
}
