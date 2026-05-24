<?php

namespace Tests\Feature\Auth;

use App\Mail\Auth\TwoFactorCodeEmail;
use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_two_factor_page_auto_sends_email_when_email_factor_required(): void
    {
        // v2.16 audit F1: the /2fa landing now auto-sends the email code
        // when the email factor is required (force_2fa_client without TOTP,
        // or new IP with email-on-new-IP). User-triggered re-send remains
        // available via the dedicated button.
        Notification::fake();
        Setting::updateSettings(['force_2fa_client' => 'true']);
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'web')->get(route('auth.2fa'));

        $response->assertOk();
        $response->assertSee(__('client.profile.2fa.send_email_code'));
        Notification::assertSentTo($customer, TwoFactorCodeEmail::class);
    }

    public function test_client_can_request_two_factor_email_code(): void
    {
        Notification::fake();
        Setting::updateSettings(['force_2fa_client' => 'true']);
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'web')->post(route('auth.2fa.email'));

        $response->assertRedirect(route('auth.2fa'));
        $response->assertSessionHas('success', __('client.profile.2fa.email_sent'));
        Notification::assertSentTo($customer, TwoFactorCodeEmail::class);
        $this->assertNotNull($customer->fresh()->getMetadata('2fa_email_code'));
        $this->assertNotNull($customer->fresh()->getMetadata('2fa_email_code_expires_at'));
    }

    public function test_client_can_verify_two_factor_email_code(): void
    {
        Setting::updateSettings(['force_2fa_client' => 'true']);
        $customer = Customer::factory()->create();
        $customer->attachMetadata('2fa_email_code', Hash::make('123456'));
        $customer->attachMetadata('2fa_email_code_expires_at', now()->addMinutes(5)->toDateTimeString());

        // v2.16 audit F1: trust is now opt-in via the trust_device checkbox.
        $response = $this->actingAs($customer, 'web')->post(route('auth.2fa'), [
            '2fa' => '123456',
            'trust_device' => '1',
        ]);

        $response->assertRedirect('/client');
        $this->assertTrue(session()->get('2fa_verified'));
        $customer = $customer->fresh();
        $this->assertNull($customer->getMetadata('2fa_email_code'));
        $this->assertContains('127.0.0.1', array_column($customer->twoFactorTrustedIps(), 'ip'));
    }

    public function test_admin_can_request_two_factor_email_code(): void
    {
        Notification::fake();
        Setting::updateSettings(['force_2fa_admin' => 'true']);
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.auth.2fa.email'));

        $response->assertRedirect(route('admin.auth.2fa'));
        $response->assertSessionHas('success', __('client.profile.2fa.email_sent'));
        Notification::assertSentTo($admin, TwoFactorCodeEmail::class);
        $this->assertNotNull($admin->fresh()->getMetadata('2fa_email_code'));
        $this->assertNotNull($admin->fresh()->getMetadata('2fa_email_code_expires_at'));
    }
}
