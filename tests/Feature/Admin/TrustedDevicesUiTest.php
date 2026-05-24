<?php

namespace Tests\Feature\Admin;

use App\Models\Admin\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin-side mirror of TrustedDevicesUiTest. Lighter coverage: we trust the
 * customer-side test for thoroughness and only pin that the admin endpoints
 * exist and apply the same revoke contract.
 */
class TrustedDevicesUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_revoke_single_device(): void
    {
        $admin = Admin::factory()->create();
        $admin->trustTwoFactorIp('1.2.3.4');
        $admin->trustTwoFactorIp('5.6.7.8');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.profile.2fa_trusted_revoke'), ['ip' => '1.2.3.4'])
            ->assertRedirect();

        $ips = array_column($admin->fresh()->twoFactorTrustedIps(), 'ip');
        $this->assertNotContains('1.2.3.4', $ips);
        $this->assertContains('5.6.7.8', $ips);
    }

    public function test_admin_revoke_all_wipes_the_list(): void
    {
        $admin = Admin::factory()->create();
        $admin->trustTwoFactorIp('1.2.3.4');
        $admin->trustTwoFactorIp('5.6.7.8');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.profile.2fa_trusted_revoke_all'))
            ->assertRedirect();

        $this->assertEmpty($admin->fresh()->twoFactorTrustedIps());
    }

    public function test_admin_revoke_validates_ip_format(): void
    {
        $admin = Admin::factory()->create();
        $admin->trustTwoFactorIp('1.2.3.4');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.profile.2fa_trusted_revoke'), ['ip' => 'not-an-ip'])
            ->assertSessionHasErrors('ip');

        $this->assertCount(1, $admin->fresh()->twoFactorTrustedIps());
    }
}
