<?php

namespace Tests\Unit\Models\Admin;

use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'smtp-secret-value';

    public function test_a_sensitive_setting_is_not_stored_in_clear(): void
    {
        Setting::updateSettings(['mail_smtp_password' => self::SECRET], null, false);

        $stored = DB::table('settings')->where('name', 'mail_smtp_password')->value('value');

        $this->assertNotSame(self::SECRET, $stored, 'the secret must not be readable straight from the table');
        $this->assertStringNotContainsString(self::SECRET, (string) $stored);
    }

    public function test_a_sensitive_setting_reads_back_in_clear(): void
    {
        Setting::updateSettings(['mail_smtp_password' => self::SECRET], null, false);

        $this->assertSame(self::SECRET, Setting::where('name', 'mail_smtp_password')->first()->value);
    }

    public function test_an_ordinary_setting_stays_in_clear(): void
    {
        Setting::updateSettings(['app_name' => 'Cerbonix'], null, false);

        $this->assertSame('Cerbonix', DB::table('settings')->where('name', 'app_name')->value('value'));
    }

    public function test_a_value_written_before_encryption_is_still_readable(): void
    {
        DB::table('settings')->insert(['name' => 'captcha_secret_key', 'value' => 'legacy-clear-value']);

        $this->assertSame('legacy-clear-value', Setting::where('name', 'captcha_secret_key')->first()->value);
    }

    public function test_saving_twice_does_not_encrypt_twice(): void
    {
        Setting::updateSettings(['mfa_sms_twilio_token' => self::SECRET], null, false);
        $setting = Setting::where('name', 'mfa_sms_twilio_token')->first();
        $setting->save();

        $this->assertSame(self::SECRET, Setting::where('name', 'mfa_sms_twilio_token')->first()->value);
    }

    public function test_a_newly_listed_secret_is_masked_in_the_audit_trail(): void
    {
        Setting::updateSettings(['mfa_sms_twilio_token' => 'old-token'], null, false);
        Setting::updateSettings(['mfa_sms_twilio_token' => self::SECRET]);

        $entries = DB::table('action_log_entries')->where('attribute', 'mfa_sms_twilio_token')->get();

        $this->assertNotEmpty($entries, 'changing a setting must leave a trace');
        foreach ($entries as $entry) {
            $this->assertSame('Encrypted', $entry->new_value, 'the audit trail must mask the value of a secret setting');
            $this->assertNotSame(self::SECRET, $entry->old_value);
        }
    }
}
