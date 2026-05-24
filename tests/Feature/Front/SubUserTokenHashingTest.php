<?php

namespace Tests\Feature\Front;

use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * S2 + S4 of the Subusers audit.
 *
 * S2: tokens used to live in the database verbatim. A DB leak (SQLi,
 * backup theft, accidental dump in support tickets) handed every
 * pending invitation to the attacker. Now the DB only holds the
 * sha256 of the token, and the plain value lives just long enough
 * to render the outgoing email.
 *
 * S4: resending an invitation used to keep the same token. If the
 * original mail leaked (forwarded by mistake, copy-pasted in chat),
 * the only way to invalidate the leak is to revoke + create a new
 * one - which loses the audit trail. Resend now rotates the token,
 * so an old link goes 404 the moment a fresh one is on the wire.
 */
class SubUserTokenHashingTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_is_persisted_as_sha256_hash(): void
    {
        $invitation = $this->makeInvitation();
        $plain = $invitation->plain_text_token;

        $this->assertNotNull($plain, 'creating() must hand back the plain token via the transient attribute');
        $this->assertSame(64, strlen($plain), 'plain token must be 64 chars (Str::random) for entropy');

        $stored = $invitation->fresh()->getRawOriginal('token');
        $this->assertSame(hash('sha256', $plain), $stored, 'DB must hold the sha256, never the plain value');
        $this->assertNotSame($plain, $stored);
    }

    public function test_accept_endpoint_resolves_plain_token_via_hash(): void
    {
        $invitation = $this->makeInvitation('bob@example.com');
        $bob = Customer::factory()->create(['email' => 'bob@example.com', 'email_verified_at' => now()]);

        $this->actingAs($bob, 'web')
            ->get(route('front.subusers.accept', $invitation->plain_text_token))
            ->assertRedirect(route('front.client.index'));

        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_accept_endpoint_rejects_a_token_that_was_persisted_raw(): void
    {
        // simulate a token from a pre-S2 row by writing a raw value directly
        $owner = Customer::factory()->create(['email_verified_at' => now()]);
        $invitation = new CustomerAccountInvitation;
        $invitation->forceFill([
            'owner_customer_id' => $owner->id,
            'email' => 'eve@example.com',
            'permissions' => ['service.show'],
            'all_services' => true,
            'token' => 'plaintext-legacy-token',
            'expires_at' => now()->addDays(14),
        ])->save();

        $eve = Customer::factory()->create(['email' => 'eve@example.com', 'email_verified_at' => now()]);

        $this->actingAs($eve, 'web')
            ->get(route('front.subusers.accept', 'plaintext-legacy-token'))
            ->assertNotFound();
    }

    public function test_resend_rotates_the_token(): void
    {
        $invitation = $this->makeInvitation('bob@example.com');
        $owner = $invitation->owner;
        $oldPlain = $invitation->plain_text_token;
        $oldHash = $invitation->fresh()->getRawOriginal('token');

        $this->actingAs($owner, 'web')
            ->post(route('front.subusers.invitations.resend', $invitation->id))
            ->assertRedirect();

        $newHash = $invitation->fresh()->getRawOriginal('token');
        $this->assertNotSame($oldHash, $newHash, 'resend must rotate the stored hash');

        // The old plain token must no longer accept.
        $bob = Customer::factory()->create(['email' => 'bob@example.com', 'email_verified_at' => now()]);
        $this->actingAs($bob, 'web')
            ->get(route('front.subusers.accept', $oldPlain))
            ->assertNotFound();
    }

    private function makeInvitation(string $email = 'invitee@example.com'): CustomerAccountInvitation
    {
        $owner = Customer::factory()->create(['email_verified_at' => now()]);

        return CustomerAccountInvitation::create([
            'owner_customer_id' => $owner->id,
            'email' => $email,
            'permissions' => ['service.show'],
            'all_services' => true,
        ]);
    }
}
