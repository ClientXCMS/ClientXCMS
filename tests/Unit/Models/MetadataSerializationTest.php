<?php

namespace Tests\Unit\Models;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetadataSerializationTest extends TestCase
{
    use RefreshDatabase;

    private const SENSITIVE_KEYS = [
        '2fa_secret',
        '2fa_recovery_codes',
        '2fa_email_code',
        '2fa_sms_code',
        '2fa_trusted_ips',
        'social_github_refresh_token',
        'social_discord_id',
        'autologin_key',
        'signup_social_pending',
        'pbs_password',
        'extension_api_token',
        'webhook_secret',
    ];

    private const PUBLIC_KEYS = [
        'panel_username',
        'server_hostname',
    ];

    private function customerWithMetadata(): Customer
    {
        $customer = Customer::factory()->create();
        foreach ([...self::SENSITIVE_KEYS, ...self::PUBLIC_KEYS] as $key) {
            $customer->attachMetadata($key, 'value-of-'.$key);
        }

        return $customer->fresh()->load('metadata');
    }

    public function test_to_array_drops_the_sensitive_keys_and_keeps_a_list(): void
    {
        $serialized = $this->customerWithMetadata()->metadata->toArray();

        $this->assertTrue(array_is_list($serialized));
        $this->assertEqualsCanonicalizing(self::PUBLIC_KEYS, array_column($serialized, 'key'));
    }

    public function test_json_encoding_drops_the_sensitive_keys_and_keeps_a_list(): void
    {
        $customer = $this->customerWithMetadata();
        $json = json_encode($customer->metadata);
        $decoded = json_decode($json, true);

        $this->assertTrue(array_is_list($decoded));
        $this->assertEqualsCanonicalizing(self::PUBLIC_KEYS, array_column($decoded, 'key'));
        foreach (self::SENSITIVE_KEYS as $key) {
            $this->assertStringNotContainsString('value-of-'.$key, $json);
            $this->assertStringNotContainsString('value-of-'.$key, $customer->toJson());
        }
    }

    public function test_the_owner_model_still_reads_every_key(): void
    {
        $customer = $this->customerWithMetadata();

        foreach ([...self::SENSITIVE_KEYS, ...self::PUBLIC_KEYS] as $key) {
            $this->assertSame('value-of-'.$key, $customer->getMetadata($key));
        }
    }

    public function test_two_factor_keeps_working(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable('JBSWY3DPEHPK3PXP');

        $reloaded = Customer::find($customer->id)->load('metadata');

        $this->assertTrue($reloaded->twoFactorEnabled());
        $this->assertSame('JBSWY3DPEHPK3PXP', $reloaded->twoFactorSecret());
    }
}
