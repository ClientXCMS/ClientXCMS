<?php

namespace Tests\Feature\Auth;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F3.1 - changing a password must invalidate the list of trusted 2FA
 * devices.
 *
 * Rationale: a password reset is by design the action a user takes
 * when they think their account was compromised. If the attacker has
 * the password and rode it through the email-on-new-IP gate at any
 * point, their IP is still on the trusted list. Resetting the password
 * without invalidating the trust list leaves a remembered device that
 * bypasses the email factor permanently. So we kick everyone off.
 */
class PasswordChangeRevokesTrustedDevicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_password_change_revokes_all_trusted_devices(): void
    {
        $customer = Customer::factory()->create(['password' => bcrypt('old-Pass-1234')]);
        $customer->trustTwoFactorIp('1.2.3.4', 'Firefox/128');
        $customer->trustTwoFactorIp('5.6.7.8', 'Safari/17');
        $this->assertCount(2, $customer->fresh()->twoFactorTrustedIps());

        $this->actingAs($customer, 'web')
            ->post(route('front.profile.password'), [
                'currentpassword' => 'old-Pass-1234',
                'password' => 'new-Pass-5678',
                'password_confirmation' => 'new-Pass-5678',
            ])
            ->assertRedirect();

        $this->assertEmpty(
            $customer->fresh()->twoFactorTrustedIps(),
            'After a password change every trusted device must be re-verified - the password was potentially compromised'
        );
    }

    public function test_admin_password_change_revokes_all_trusted_devices(): void
    {
        $admin = Admin::factory()->create(['password' => bcrypt('old-Pass-1234')]);
        $admin->trustTwoFactorIp('1.2.3.4', 'Chrome/127');
        $this->assertCount(1, $admin->fresh()->twoFactorTrustedIps());

        $this->actingAs($admin, 'admin')
            ->put(route('admin.profile.password'), [
                'current_password' => 'old-Pass-1234',
                'password' => 'new-Pass-5678',
                'password_confirmation' => 'new-Pass-5678',
            ])
            ->assertRedirect();

        $this->assertEmpty($admin->fresh()->twoFactorTrustedIps());
    }
}
