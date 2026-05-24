<?php

namespace Tests\Feature\Front;

use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * S1 of the Subusers audit - prevent the "register-with-victim-email"
 * account takeover.
 *
 * Threat: an attacker who intercepts an invitation link (sent to a
 * fresh user who does not yet have an account) can register the email
 * to themselves, log in, and consume the invitation - getting access
 * to the inviter's services. The victim cannot recover their email
 * because the address is already taken.
 *
 * Defense: a freshly registered account has email_verified_at = null
 * until the user clicks the verification link in their mailbox.
 * Without that proof of mailbox ownership, the accept endpoint must
 * refuse to consume the token. The attacker cannot pass this gate
 * unless they also control the victim's mailbox - in which case the
 * invitation was already compromised at the email level, not at our
 * application boundary.
 */
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

        $response = $this->actingAs($bob, 'web')
            ->get(route('front.subusers.accept', $invitation->plain_text_token));

        $response->assertRedirect(route('front.client.index'));
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
