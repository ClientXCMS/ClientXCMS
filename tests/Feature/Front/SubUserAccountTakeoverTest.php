<?php

namespace Tests\Feature\Front;

use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// S1: prevent "register-with-victim-email" takeover. Attacker who intercepts
// an invitation link registers the email themselves and consumes the
// invitation. Defense: refuse to consume if email_verified_at is null
// (proof of mailbox control). Beyond that, the email channel is compromised.
class SubUserAccountTakeoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_cannot_consume_invitation(): void
    {
        $invitation = $this->pendingInvitationFor('bob@example.com');
        $attacker = Customer::factory()->unverified()->create(['email' => 'bob@example.com']);

        $response = $this->actingAs($attacker, 'web')
            ->get(route('front.subusers.accept', $invitation->plain_text_token));

        $response->assertRedirect();
        $this->assertNotEquals(
            route('front.client.index'),
            $response->headers->get('Location'),
            'Unverified consumer must NOT be sent to the success destination'
        );
        $this->assertNull(
            $invitation->fresh()->accepted_at,
            'A token consumed by an unverified email is the takeover vector - it must stay pending'
        );
    }

    public function test_verified_user_can_consume_invitation(): void
    {
        $invitation = $this->pendingInvitationFor('bob@example.com');
        $bob = Customer::factory()->create(['email' => 'bob@example.com', 'email_verified_at' => now()]);

        // GET only renders the confirmation page now - the actual
        // commit happens through POST (S3+S6).
        $this->actingAs($bob, 'web')
            ->get(route('front.subusers.accept', $invitation->plain_text_token))
            ->assertOk();

        $this->actingAs($bob, 'web')
            ->post(route('front.subusers.accept.confirm', $invitation->plain_text_token))
            ->assertRedirect(route('front.client.index'));

        $this->assertNotNull(
            $invitation->fresh()->accepted_at,
            'Legitimate verified consumer must complete the flow as before'
        );
    }

    public function test_unverified_user_is_redirected_to_send_verification_link(): void
    {
        $invitation = $this->pendingInvitationFor('bob@example.com');
        $attacker = Customer::factory()->unverified()->create(['email' => 'bob@example.com']);

        $response = $this->actingAs($attacker, 'web')
            ->get(route('front.subusers.accept', $invitation->plain_text_token));

        // UX: the user lands on the resend-verification endpoint so they
        // get a fresh link in their mailbox without an extra click.
        $response->assertRedirect(route('verification.send'));
    }

    private function pendingInvitationFor(string $email): CustomerAccountInvitation
    {
        $owner = Customer::factory()->create(['email_verified_at' => now()]);

        return CustomerAccountInvitation::create([
            'owner_customer_id' => $owner->id,
            'email' => $email,
            'permissions' => ['service.show', 'invoice.show'],
            'all_services' => true,
        ]);
    }
}
