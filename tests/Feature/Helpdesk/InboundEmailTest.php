<?php

namespace Tests\Feature\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportTicket;
use App\Services\Helpdesk\InboundEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * v2.16 — Inbound email → ticket pipeline.
 *
 * The webhook controller is mostly a payload normaliser; these tests
 * exercise the service directly with a canonical payload + the
 * controller end-to-end with a Postmark-shaped JSON to make sure the
 * full path works.
 */
class InboundEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The inbound service expects at least one department to exist
        // so the default ticket assignment doesn't fail. The model uses
        // a custom factory class (`DepartmentFactory`) which doesn't
        // follow Laravel's default lookup, so we instantiate manually.
        \Database\Factories\Helpdesk\DepartmentFactory::new()->create();

        // Provider expects a configured token. We seed it directly into
        // the settings cache via the existing helper.
        setting(['helpdesk_inbound_token' => 'TEST-TOKEN'])->save();
    }

    public function test_unknown_token_is_rejected(): void
    {
        $response = $this->postJson(
            route('webhooks.helpdesk.inbound', ['token' => 'WRONG']),
            ['from' => 'someone@example.com', 'subject' => 'Hi', 'text' => 'Body']
        );

        $response->assertStatus(403);
        $this->assertSame(0, SupportTicket::count());
    }

    public function test_generic_payload_creates_a_guest_ticket(): void
    {
        $response = $this->postJson(
            route('webhooks.helpdesk.inbound', ['token' => 'TEST-TOKEN']),
            [
                'from' => 'visitor@example.com',
                'from_name' => 'Jane Visitor',
                'subject' => 'I need help',
                'text' => 'My VPS is offline, please help.',
                'message_id' => 'unique-mid-001',
            ]
        );

        $response->assertOk();
        $this->assertSame(1, SupportTicket::count());

        $ticket = SupportTicket::first();
        $this->assertNull($ticket->customer_id);
        $this->assertSame('visitor@example.com', $ticket->guest_email);
        $this->assertSame('Jane Visitor', $ticket->guest_name);
        $this->assertSame('I need help', $ticket->subject);
        $this->assertNotEmpty($ticket->guest_token);
        $this->assertSame(1, $ticket->messages()->count());
    }

    public function test_inbound_from_existing_customer_links_to_account(): void
    {
        $customer = Customer::factory()->create(['email' => 'pro@example.com']);

        $service = app(InboundEmailService::class);
        $ticket = $service->process([
            'from_email' => 'pro@example.com',
            'from_name' => null,
            'subject' => 'Bug report',
            'body_plain' => 'Step 1, step 2, step 3.',
            'body_html' => null,
            'message_id' => 'mid-001',
            'in_reply_to' => null,
            'references' => [],
        ]);

        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertNull($ticket->guest_email);
    }

    public function test_reply_is_appended_to_existing_ticket(): void
    {
        $service = app(InboundEmailService::class);

        $first = $service->process([
            'from_email' => 'visitor@example.com',
            'from_name' => 'Jane',
            'subject' => 'Hi',
            'body_plain' => 'Initial',
            'body_html' => null,
            'message_id' => 'mid-001',
            'in_reply_to' => null,
            'references' => [],
        ]);

        $second = $service->process([
            'from_email' => 'visitor@example.com',
            'from_name' => 'Jane',
            'subject' => 'Re: Hi',
            'body_plain' => 'Follow-up',
            'body_html' => null,
            'message_id' => 'mid-002',
            'in_reply_to' => 'mid-001',
            'references' => ['mid-001'],
        ]);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(2, $second->messages()->count());
    }

    public function test_duplicate_message_id_is_idempotent(): void
    {
        $service = app(InboundEmailService::class);

        $service->process([
            'from_email' => 'visitor@example.com',
            'from_name' => null,
            'subject' => 'Hi',
            'body_plain' => 'Body',
            'body_html' => null,
            'message_id' => 'duplicate-mid',
            'in_reply_to' => null,
            'references' => [],
        ]);
        $service->process([
            'from_email' => 'visitor@example.com',
            'from_name' => null,
            'subject' => 'Hi',
            'body_plain' => 'Body',
            'body_html' => null,
            'message_id' => 'duplicate-mid',
            'in_reply_to' => null,
            'references' => [],
        ]);

        $this->assertSame(1, SupportTicket::count());
        $this->assertSame(1, SupportTicket::first()->messages()->count());
    }
}
