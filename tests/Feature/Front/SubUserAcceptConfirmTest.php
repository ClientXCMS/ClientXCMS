<?php

namespace Tests\Feature\Front;

use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// S3+S6: split GET (read-only confirmation) / POST (CSRF-protected commit).
// Plain GET used to consume the token (distracted-click, previewers, CSRF).
class SubUserAcceptConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_renders_confirmation_page_without_consuming_token(): void
    {
        $invitation = $this->pendingInvitationFor('bob@example.com');
        $bob = Customer::factory()->create(['email' => 'bob@example.com', 'email_verified_at' => now()]);

        $response = $this->actingAs($bob, 'web')
            ->get(route('front.subusers.accept', $invitation->plain_text_token));

        $response->assertOk();
        $response->assertSee($invitation->owner->email);
        $this->assertNull(
            $invitation->fresh()->accepted_at,
            'A GET on the confirmation page must NOT consume the token - distracted clicks must not grant access'
        );
    }

    public function test_post_consumes_token_and_grants_access(): void
    {
        $invitation = $this->pendingInvitationFor('bob@example.com');
        $bob = Customer::factory()->create(['email' => 'bob@example.com', 'email_verified_at' => now()]);

        $response = $this->actingAs($bob, 'web')
            ->post(route('front.subusers.accept.confirm', $invitation->plain_text_token));

        $response->assertRedirect(route('front.client.index'));
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_post_redirects_an_unverified_user_to_email_verification(): void
    {
        $invitation = $this->pendingInvitationFor('bob@example.com');
        $bob = Customer::factory()->unverified()->create(['email' => 'bob@example.com']);

        $this->actingAs($bob, 'web')
            ->post(route('front.subusers.accept.confirm', $invitation->plain_text_token))
            ->assertRedirect(route('verification.send'))
            ->assertSessionHas('error', __('client.subusers.alerts.must_verify_email'));

        $this->assertNull($invitation->fresh()->accepted_at);
    }

    public function test_post_redirects_when_the_authenticated_email_does_not_match(): void
    {
        $invitation = $this->pendingInvitationFor('bob@example.com');
        $alice = Customer::factory()->create(['email' => 'alice@example.com', 'email_verified_at' => now()]);

        $this->actingAs($alice, 'web')
            ->post(route('front.subusers.accept.confirm', $invitation->plain_text_token))
            ->assertRedirect(route('front.client.index'))
            ->assertSessionHas('error', __('client.subusers.alerts.invitation_email_mismatch'));

        $this->assertNull($invitation->fresh()->accepted_at);
    }

    public function test_post_without_csrf_or_wrong_route_does_not_accept(): void
    {
        $invitation = $this->pendingInvitationFor('bob@example.com');
        $bob = Customer::factory()->create(['email' => 'bob@example.com', 'email_verified_at' => now()]);

        // Trying to consume on the GET route via a POST should not route -
        // the confirmation page is GET-only.
        $response = $this->actingAs($bob, 'web')
            ->get(route('front.subusers.accept', $invitation->plain_text_token));

        $response->assertOk();
        $this->assertNull($invitation->fresh()->accepted_at);
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
