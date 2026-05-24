<?php

namespace Tests\Feature\Client;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Locks the customer-facing "Trusted devices" management UI.
 *
 * Endpoints:
 *   POST /client/profile/2fa/trusted/revoke      ?ip=...
 *   POST /client/profile/2fa/trusted/revoke-all
 *
 * The list itself is rendered on the existing profile edit page.
 */
class TrustedDevicesUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_edit_lists_trusted_devices_with_ip_and_expiry(): void
    {
        $customer = Customer::factory()->create();
        $customer->trustTwoFactorIp('1.2.3.4', 'Mozilla/5.0 Firefox/128');
        $customer->trustTwoFactorIp('5.6.7.8', 'Mozilla/5.0 Chrome/127 Windows');

        $response = $this->actingAs($customer, 'web')->get(route('front.profile.index'));

        $response->assertOk();
        $response->assertSee('1.2.3.4');
        $response->assertSee('5.6.7.8');
        $response->assertSee('Firefox', false);
        $response->assertSee('Chrome', false);
    }

    public function test_revoke_single_device_endpoint_drops_only_one_entry(): void
    {
        $customer = Customer::factory()->create();
        $customer->trustTwoFactorIp('1.2.3.4');
        $customer->trustTwoFactorIp('5.6.7.8');

        $this->actingAs($customer, 'web')
            ->post(route('front.profile.2fa_trusted_revoke'), ['ip' => '1.2.3.4'])
            ->assertRedirect();

        $ips = array_column($customer->fresh()->twoFactorTrustedIps(), 'ip');
        $this->assertNotContains('1.2.3.4', $ips);
        $this->assertContains('5.6.7.8', $ips);
    }

    public function test_revoke_single_device_validates_ip_format(): void
    {
        $customer = Customer::factory()->create();
        $customer->trustTwoFactorIp('1.2.3.4');

        $this->actingAs($customer, 'web')
            ->post(route('front.profile.2fa_trusted_revoke'), ['ip' => '<script>alert(1)</script>'])
            ->assertSessionHasErrors('ip');

        $this->assertCount(1, $customer->fresh()->twoFactorTrustedIps());
    }

    public function test_revoke_all_endpoint_wipes_the_list(): void
    {
        $customer = Customer::factory()->create();
        $customer->trustTwoFactorIp('1.2.3.4');
        $customer->trustTwoFactorIp('5.6.7.8');
        $customer->trustTwoFactorIp('9.9.9.9');

        $this->actingAs($customer, 'web')
            ->post(route('front.profile.2fa_trusted_revoke_all'))
            ->assertRedirect();

        $this->assertEmpty($customer->fresh()->twoFactorTrustedIps());
    }

    public function test_current_ip_is_visually_marked(): void
    {
        $customer = Customer::factory()->create();
        $customer->trustTwoFactorIp('127.0.0.1', 'Mozilla/5.0');

        $response = $this->actingAs($customer, 'web')->get(route('front.profile.index'));

        $response->assertSee(__('client.profile.2fa.current_device'));
    }
}
