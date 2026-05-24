<?php

namespace Tests\Feature\Models;

use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Locks the trusted-IP storage contract for the email-2FA-on-new-IP feature.
 *
 * Storage shape: an array of {ip, until} entries. `until` is null for legacy
 * permanent trust (rows written before this commit) and an ISO datetime for
 * time-bound trust set via the "Trust this device 30 days" checkbox.
 *
 * The `until` window is sourced from setting('trust_device_days', 30) so
 * operators can tune the policy per instance without a code change.
 */
class TrustedTwoFactorIpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_trust_stores_ip_with_expiration_from_setting(): void
    {
        Setting::updateSettings(['trust_device_days' => '30']);
        $customer = Customer::factory()->create();
        Carbon::setTestNow('2026-05-24 10:00:00');

        $customer->trustTwoFactorIp('1.2.3.4');

        $entries = $customer->fresh()->twoFactorTrustedIps();
        $this->assertCount(1, $entries);
        $this->assertSame('1.2.3.4', $entries[0]['ip']);
        $this->assertSame('2026-06-23 10:00:00', $entries[0]['until']);
    }

    public function test_trust_window_follows_setting_override(): void
    {
        Setting::updateSettings(['trust_device_days' => '7']);
        $customer = Customer::factory()->create();
        Carbon::setTestNow('2026-05-24 10:00:00');

        $customer->trustTwoFactorIp('1.2.3.4');

        $this->assertSame('2026-05-31 10:00:00', $customer->fresh()->twoFactorTrustedIps()[0]['until']);
    }

    public function test_expired_entries_are_not_returned(): void
    {
        $customer = Customer::factory()->create();
        Carbon::setTestNow('2026-05-01 10:00:00');
        $customer->trustTwoFactorIp('1.2.3.4');

        Carbon::setTestNow('2026-08-01 10:00:00'); // > 30 days later

        $this->assertEmpty($customer->fresh()->twoFactorTrustedIps());
        $this->assertTrue(
            $this->isUntrustedFor($customer->fresh(), '1.2.3.4'),
            'Expired trust must not bypass the email-on-new-IP gate'
        );
    }

    public function test_legacy_string_array_is_treated_as_permanent_trust(): void
    {
        $customer = Customer::factory()->create();
        // pre-v2.16-audit shape: bare array of IP strings
        $customer->attachMetadata('2fa_trusted_ips', json_encode(['9.9.9.9', '8.8.8.8']));

        $entries = $customer->fresh()->twoFactorTrustedIps();

        $this->assertCount(2, $entries);
        $this->assertSame('9.9.9.9', $entries[0]['ip']);
        $this->assertNull($entries[0]['until'], 'Legacy entries get null = trust forever, so existing users are not silently kicked off');
        $this->assertFalse($this->isUntrustedFor($customer->fresh(), '9.9.9.9'));
    }

    public function test_trust_is_capped_to_twenty_entries_lru(): void
    {
        $customer = Customer::factory()->create();

        for ($i = 1; $i <= 25; $i++) {
            $customer->trustTwoFactorIp("10.0.0.{$i}");
        }

        $entries = $customer->fresh()->twoFactorTrustedIps();
        $this->assertCount(20, $entries);
        $ips = array_column($entries, 'ip');
        $this->assertNotContains('10.0.0.1', $ips, 'Oldest entries must be evicted first');
        $this->assertContains('10.0.0.25', $ips, 'Newest entry must be kept');
    }

    public function test_retrusting_same_ip_refreshes_expiry_without_duplicating(): void
    {
        Setting::updateSettings(['trust_device_days' => '30']);
        $customer = Customer::factory()->create();
        Carbon::setTestNow('2026-05-01 10:00:00');
        $customer->trustTwoFactorIp('1.2.3.4');

        Carbon::setTestNow('2026-05-15 10:00:00');
        $customer->trustTwoFactorIp('1.2.3.4');

        $entries = $customer->fresh()->twoFactorTrustedIps();
        $this->assertCount(1, $entries);
        $this->assertSame('2026-06-14 10:00:00', $entries[0]['until'], 'Re-trust must extend the window from now, not stack');
    }

    public function test_trust_ignored_when_ip_is_null(): void
    {
        $customer = Customer::factory()->create();
        $customer->trustTwoFactorIp(null);

        $this->assertEmpty($customer->fresh()->twoFactorTrustedIps());
    }

    private function isUntrustedFor(Customer $customer, string $ip): bool
    {
        $customer->setTwoFactorEmailOnNewIp(true);

        return $customer->requiresEmailTwoFactorForIp($ip);
    }
}
