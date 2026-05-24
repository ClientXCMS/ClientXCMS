<?php

namespace Tests\Feature\Auth;

use App\Mail\Auth\TwoFactorCodeEmail;
use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Locks the two-step 2FA flow for the new-IP / dual-factor path (F1).
 *
 * Threat model: an attacker with the password and access to the user's
 * mailbox should still be blocked by the TOTP gate. Conversely, a TOTP
 * thief who can't read the user's mailbox should be blocked at the
 * email gate. Email and TOTP must be required *together* on untrusted
 * IPs, not interchangeably.
 *
 * The trusted-IP single-factor path (TOTP-only on a known IP) remains
 * unchanged so legitimate users don't get punished for the new defense.
 */
class TwoFactorStepFlowTest extends TestCase
{
    use RefreshDatabase;

    private const TOTP_SECRET = 'JBSWY3DPEHPK3PXP';

    public function test_dual_factor_get_renders_step1_when_totp_not_yet_verified(): void
    {
        $customer = $this->customerOnUntrustedIp();

        $response = $this->actingAs($customer, 'web')->get(route('auth.2fa'));

        $response->assertOk();
        $response->assertSee('name="2fa"', false);
        $response->assertSessionMissing('2fa_totp_verified');
    }

    public function test_dual_factor_post_step1_with_valid_totp_holds_in_session_without_completing(): void
    {
        Notification::fake();
        $customer = $this->customerOnUntrustedIp();
        $code = $this->currentTotp(self::TOTP_SECRET);

        $response = $this->actingAs($customer, 'web')
            ->post(route('auth.2fa'), ['2fa' => $code]);

        $response->assertRedirect(route('auth.2fa'));
        $this->assertTrue(session()->get('2fa_totp_verified'));
        $this->assertFalse(session()->get('2fa_verified', false), 'Step 1 must NOT complete 2FA on its own when email is also required');
    }

    public function test_dual_factor_get_step2_auto_sends_email_when_no_active_code(): void
    {
        Notification::fake();
        $customer = $this->customerOnUntrustedIp();

        $this->actingAs($customer, 'web')
            ->withSession(['2fa_totp_verified' => true])
            ->get(route('auth.2fa'))
            ->assertOk();

        Notification::assertSentTo($customer, TwoFactorCodeEmail::class);
    }

    public function test_dual_factor_post_step2_with_valid_email_completes_2fa(): void
    {
        $customer = $this->customerOnUntrustedIp();
        $customer->attachMetadata('2fa_email_code', Hash::make('123456'));
        $customer->attachMetadata('2fa_email_code_expires_at', now()->addMinutes(5)->toDateTimeString());

        $response = $this->actingAs($customer, 'web')
            ->withSession(['2fa_totp_verified' => true])
            ->post(route('auth.2fa'), ['2fa' => '123456']);

        $response->assertRedirect('/client');
        $this->assertTrue(session()->get('2fa_verified'));
        $this->assertNull(session()->get('2fa_totp_verified'), 'Step 1 flag must be cleared once full 2FA is achieved');
    }

    public function test_trust_device_checkbox_records_ip_only_when_ticked(): void
    {
        $customer = $this->customerOnUntrustedIp();
        $customer->attachMetadata('2fa_email_code', Hash::make('123456'));
        $customer->attachMetadata('2fa_email_code_expires_at', now()->addMinutes(5)->toDateTimeString());

        $this->actingAs($customer, 'web')
            ->withSession(['2fa_totp_verified' => true])
            ->post(route('auth.2fa'), ['2fa' => '123456', 'trust_device' => '1']);

        $ips = array_column($customer->fresh()->twoFactorTrustedIps(), 'ip');
        $this->assertContains('127.0.0.1', $ips);
    }

    public function test_no_trust_device_means_ip_not_recorded(): void
    {
        $customer = $this->customerOnUntrustedIp();
        $customer->attachMetadata('2fa_email_code', Hash::make('123456'));
        $customer->attachMetadata('2fa_email_code_expires_at', now()->addMinutes(5)->toDateTimeString());

        $this->actingAs($customer, 'web')
            ->withSession(['2fa_totp_verified' => true])
            ->post(route('auth.2fa'), ['2fa' => '123456']);

        $this->assertEmpty(
            $customer->fresh()->twoFactorTrustedIps(),
            'Without explicit consent the IP must not be remembered - users decide what gets trusted'
        );
    }

    public function test_recovery_code_satisfies_step1_but_email_still_required(): void
    {
        Notification::fake();
        $customer = $this->customerOnUntrustedIp();
        $recovery = $customer->twoFactorRecoveryCodes()[0];

        $response = $this->actingAs($customer, 'web')
            ->post(route('auth.2fa'), ['2fa' => $recovery]);

        $response->assertRedirect(route('auth.2fa'));
        $this->assertTrue(session()->get('2fa_totp_verified'));
        $this->assertFalse(session()->get('2fa_verified', false), 'Recovery is the device-loss escape hatch but does NOT bypass email gate on untrusted IPs');
    }

    public function test_reset_endpoint_clears_step1_flag(): void
    {
        $customer = $this->customerOnUntrustedIp();

        $response = $this->actingAs($customer, 'web')
            ->withSession(['2fa_totp_verified' => true])
            ->post(route('auth.2fa.reset'));

        $response->assertRedirect(route('auth.2fa'));
        $this->assertNull(session()->get('2fa_totp_verified'));
    }

    public function test_totp_only_flow_on_trusted_ip_completes_in_one_step(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable(self::TOTP_SECRET);
        // Note: deliberately NOT enabling email-on-new-IP - this is the
        // baseline pre-feature flow and must still work in one shot.

        $response = $this->actingAs($customer, 'web')
            ->post(route('auth.2fa'), ['2fa' => $this->currentTotp(self::TOTP_SECRET)]);

        $response->assertRedirect('/client');
        $this->assertTrue(session()->get('2fa_verified'));
    }

    public function test_trusted_ip_bypasses_email_factor_even_with_email_on_new_ip_enabled(): void
    {
        Notification::fake();
        $customer = $this->customerOnUntrustedIp();
        // Mark our test IP as trusted (within the 30-day window).
        $customer->trustTwoFactorIp('127.0.0.1');

        $response = $this->actingAs($customer->fresh(), 'web')
            ->post(route('auth.2fa'), ['2fa' => $this->currentTotp(self::TOTP_SECRET)]);

        $response->assertRedirect('/client');
        $this->assertTrue(session()->get('2fa_verified'));
        Notification::assertNothingSent();
    }

    public function test_expired_trust_re_engages_email_factor(): void
    {
        Notification::fake();
        $customer = $this->customerOnUntrustedIp();
        // Pretend we trusted this IP a long time ago - past the 30-day window.
        \Illuminate\Support\Carbon::setTestNow('2026-01-01 10:00:00');
        $customer->trustTwoFactorIp('127.0.0.1');
        \Illuminate\Support\Carbon::setTestNow('2026-05-01 10:00:00');

        $response = $this->actingAs($customer->fresh(), 'web')
            ->post(route('auth.2fa'), ['2fa' => $this->currentTotp(self::TOTP_SECRET)]);

        // Should land on step 2 (email), not /client - expired trust counts as new IP.
        $response->assertRedirect(route('auth.2fa'));
        $this->assertTrue(session()->get('2fa_totp_verified'));
        $this->assertFalse(session()->get('2fa_verified', false));
    }

    public function test_full_happy_path_dual_factor_walk_through(): void
    {
        Notification::fake();
        $customer = $this->customerOnUntrustedIp();

        // -- 1) Land on /2fa: step 1 (TOTP) is rendered.
        $this->actingAs($customer, 'web')
            ->get(route('auth.2fa'))
            ->assertOk()
            ->assertSee(__('client.profile.2fa.step1_subheading'));
        Notification::assertNothingSent();

        // -- 2) Submit TOTP: redirect to /2fa, totp flag set.
        $this->post(route('auth.2fa'), ['2fa' => $this->currentTotp(self::TOTP_SECRET)])
            ->assertRedirect(route('auth.2fa'));
        $this->assertTrue(session()->get('2fa_totp_verified'));
        $this->assertFalse(session()->get('2fa_verified', false));

        // -- 3) Land on /2fa again: step 2 rendered, auto-send fires.
        $this->get(route('auth.2fa'))->assertOk()->assertSee(__('client.profile.2fa.step2_subheading'));
        Notification::assertSentTo($customer, TwoFactorCodeEmail::class);

        // The auto-sent code is only hashed in metadata, so we can't read it
        // back. Seed a known value to drive the verify step (same pattern as
        // other tests in the suite).
        $customer->attachMetadata('2fa_email_code', \Illuminate\Support\Facades\Hash::make('424242'));
        $customer->attachMetadata('2fa_email_code_expires_at', now()->addMinutes(5)->toDateTimeString());

        // -- 4) Submit email code with trust_device ticked: complete + trust IP.
        $this->post(route('auth.2fa'), ['2fa' => '424242', 'trust_device' => '1'])
            ->assertRedirect('/client');

        $this->assertTrue(session()->get('2fa_verified'));
        $this->assertNull(session()->get('2fa_totp_verified'), 'Step 1 flag must be cleared on full success');
        $this->assertContains('127.0.0.1', array_column($customer->fresh()->twoFactorTrustedIps(), 'ip'));
    }

    public function test_step2_view_displays_cooldown_banner_when_active(): void
    {
        Notification::fake();
        $customer = $this->customerOnUntrustedIp();
        $customer->attachMetadata('2fa_email_burned_cycles', '3');
        $customer->attachMetadata('2fa_email_burned_at', now()->toDateTimeString());

        $response = $this->actingAs($customer->fresh(), 'web')
            ->withSession(['2fa_totp_verified' => true])
            ->get(route('auth.2fa'));

        $response->assertOk();
        $response->assertSee(__('client.profile.2fa.cooldown_active', ['minutes' => 5]));
        Notification::assertNothingSent();
    }

    public function test_send_email_code_route_surfaces_cooldown_error(): void
    {
        $customer = $this->customerOnUntrustedIp();
        // Push the user past the cooldown threshold directly.
        $customer->attachMetadata('2fa_email_burned_cycles', '3');
        $customer->attachMetadata('2fa_email_burned_at', now()->toDateTimeString());

        $response = $this->actingAs($customer->fresh(), 'web')
            ->withSession(['2fa_totp_verified' => true])
            ->post(route('auth.2fa.email'));

        $response->assertRedirect(route('auth.2fa'));
        $response->assertSessionHas('error');
    }

    private function customerOnUntrustedIp(): Customer
    {
        Setting::updateSettings(['force_2fa_client' => 'true']);
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable(self::TOTP_SECRET);
        $customer->setTwoFactorEmailOnNewIp(true);
        // twoFactorEnable seeds the session 2fa_verified=true so the just-
        // activated user is not bounced - but that pollutes our test session.
        session()->forget('2fa_verified');

        return $customer->fresh();
    }

    private function currentTotp(string $secret): string
    {
        return (new \PragmaRX\Google2FA\Google2FA)->getCurrentOtp($secret);
    }
}
