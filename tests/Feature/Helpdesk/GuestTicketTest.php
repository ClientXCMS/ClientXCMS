<?php

namespace Tests\Feature\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * v2.16 — Anonymous (no-account) ticket flow.
 */
class GuestTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Database\Factories\Helpdesk\DepartmentFactory::new()->create();
    }

    public function test_visitor_can_open_a_guest_ticket(): void
    {
        $response = $this->post(route('front.support.guest.store'), [
            'email' => 'visitor@example.com',
            'name' => 'Curious Visitor',
            'subject' => 'Where do I sign up?',
            'message' => 'Just exploring before creating an account.',
        ]);

        $ticket = SupportTicket::first();
        $this->assertNotNull($ticket, 'A ticket should have been created.');
        $this->assertNull($ticket->customer_id);
        $this->assertSame('visitor@example.com', $ticket->guest_email);
        $this->assertSame('Curious Visitor', $ticket->guest_name);
        $this->assertNotEmpty($ticket->guest_token);

        $response->assertRedirect(route('front.support.guest.track', ['token' => $ticket->guest_token]));
    }

    public function test_existing_customer_email_attaches_ticket_to_account(): void
    {
        $customer = Customer::factory()->create(['email' => 'pro@example.com']);

        $this->post(route('front.support.guest.store'), [
            'email' => 'pro@example.com',
            'subject' => 'Help',
            'message' => 'Already a client here.',
        ]);

        $ticket = SupportTicket::first();
        $this->assertSame($customer->id, $ticket->customer_id);
        // guest_email stays null because the address matched an account
        $this->assertNull($ticket->guest_email);
        // …but the guest_token is still minted so the customer can land
        // on the tracking page without logging in.
        $this->assertNotEmpty($ticket->guest_token);
    }

    public function test_unknown_token_404s(): void
    {
        $this->get(route('front.support.guest.track', ['token' => str_repeat('x', 48)]))
            ->assertNotFound();
    }

    public function test_visitor_can_reply_via_guest_token(): void
    {
        $this->post(route('front.support.guest.store'), [
            'email' => 'visitor@example.com',
            'subject' => 'Hi there',
            'message' => 'Initial message',
        ]);
        $ticket = SupportTicket::first();

        $this->post(route('front.support.guest.reply', ['token' => $ticket->guest_token]), [
            'message' => 'Just following up',
        ])->assertRedirect(route('front.support.guest.track', ['token' => $ticket->guest_token]));

        $this->assertSame(2, $ticket->fresh()->messages()->count());
    }
}
