<?php

namespace Tests\Unit\Services\Auth;

use App\Models\Admin\Setting;
use App\Services\Auth\MfaConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MfaConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_match_config_file(): void
    {
        $this->assertSame(config('mfa.email.max_attempts'), MfaConfig::emailMaxAttempts());
        $this->assertSame(config('mfa.email.max_cycles'), MfaConfig::emailMaxCycles());
        $this->assertSame(config('mfa.email.cooldown_minutes'), MfaConfig::emailCooldownMinutes());
        $this->assertSame(config('mfa.sms.daily_cap'), MfaConfig::smsDailyCap());
        $this->assertSame(config('mfa.trusted_devices.max_entries'), MfaConfig::trustedDevicesMax());
    }

    public function test_setting_overrides_config_value(): void
    {
        Setting::updateSettings(['mfa_email_max_attempts' => 8]);
        $this->assertSame(8, MfaConfig::emailMaxAttempts());

        Setting::updateSettings(['mfa_sms_daily_cap' => 3]);
        $this->assertSame(3, MfaConfig::smsDailyCap());
    }

    public function test_empty_setting_falls_back_to_config(): void
    {
        Setting::updateSettings(['mfa_email_cooldown_minutes' => '']);
        $this->assertSame(config('mfa.email.cooldown_minutes'), MfaConfig::emailCooldownMinutes());
    }

    public function test_force_for_admin_and_client_are_independent(): void
    {
        Setting::updateSettings(['force_2fa_admin' => 'true', 'force_2fa_client' => 'false']);
        $this->assertTrue(MfaConfig::forceFor('admin'));
        $this->assertFalse(MfaConfig::forceFor('client'));
    }

    public function test_trust_device_lifetime_clamps_to_at_least_one_day(): void
    {
        Setting::updateSettings(['trust_device_days' => 0]);
        $this->assertSame(1, MfaConfig::trustedDeviceLifetimeDays());
    }
}
