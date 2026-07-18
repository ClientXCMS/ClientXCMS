<?php

namespace Tests\Feature\Front;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * S5 of the Subusers audit - cap how often an authenticated user can
 * trigger transactional invitation emails. Each invitation send hits
 * an arbitrary recipient address chosen by the inviter, so without a
 * cap a malicious (or compromised) account can use the platform as
 * an email-relay against any target.
 */
class SubUserInvitationThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_create_is_rate_limited(): void
    {
        $owner = Customer::factory()->create(['email_verified_at' => now()]);

        $payload = [
            'email' => 'target@example.com',
            'permissions' => ['service.show'],
            'all_services' => '1',
        ];

        // The 10 first requests must go through (validation errors are fine,
        // we only care that the throttle didn't fire yet).
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($owner, 'web')
                ->post(route('front.subusers.store'), array_merge($payload, [
                    'email' => "target{$i}@example.com",
                ]))
                ->assertStatus(302);
        }

        $response = $this->actingAs($owner, 'web')
            ->post(route('front.subusers.store'), array_merge($payload, [
                'email' => 'target-flood@example.com',
            ]));

        $this->assertSame(
            429,
            $response->status(),
            'After 10 invitations in a minute the cap must trip - otherwise the platform becomes an email cannon'
        );
    }
}
