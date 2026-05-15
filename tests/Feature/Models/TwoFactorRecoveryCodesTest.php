<?php

namespace Tests\Feature\Models;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TwoFactorRecoveryCodesTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_codes_are_encrypted_at_rest(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable('JBSWY3DPEHPK3PXP');

        $stored = $customer->getMetadata('2fa_recovery_codes');
        $this->assertNotEmpty($stored);

        $codes = $customer->twoFactorRecoveryCodes();
        $sample = $codes[0];
        $this->assertStringNotContainsString(
            $sample,
            $stored,
            'Recovery codes must not be visible in metadata - DB leak (SQLi, backup) would expose every active 2FA factor in plaintext'
        );

        $this->assertSame(
            implode(',', $codes),
            Crypt::decryptString($stored),
            'Stored value must be a Crypt::encryptString of the joined codes (so admin/user can still download them)'
        );
    }

    public function test_valid_code_passes_via_constant_time_compare(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable('JBSWY3DPEHPK3PXP');
        $code = $customer->twoFactorRecoveryCodes()[2];

        $this->assertTrue($customer->fresh()->isValidRecoveryCode($code));
    }

    public function test_invalid_code_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable('JBSWY3DPEHPK3PXP');

        $this->assertFalse($customer->fresh()->isValidRecoveryCode('not-a-real-code'));
        $this->assertFalse($customer->fresh()->isValidRecoveryCode(''));
    }

    public function test_used_recovery_code_is_consumed(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable('JBSWY3DPEHPK3PXP');
        $code = $customer->twoFactorRecoveryCodes()[0];
        $remaining = count($customer->twoFactorRecoveryCodes());

        $customer->useRecoveryCode($code);

        $this->assertCount($remaining - 1, $customer->fresh()->twoFactorRecoveryCodes());
        $this->assertFalse($customer->fresh()->isValidRecoveryCode($code), 'A used recovery code must not be reusable');
    }

    public function test_legacy_plaintext_metadata_still_works(): void
    {
        $customer = Customer::factory()->create();
        $legacyCodes = ['legacy-aaaa-1111', 'legacy-bbbb-2222'];
        $customer->attachMetadata('2fa_recovery_codes', implode(',', $legacyCodes));

        $this->assertSame($legacyCodes, $customer->fresh()->twoFactorRecoveryCodes(), 'Pre-fix plaintext rows must keep working');
        $this->assertTrue($customer->fresh()->isValidRecoveryCode('legacy-aaaa-1111'));
    }

    public function test_compare_uses_hash_equals_in_source(): void
    {
        $src = file_get_contents(app_path('Models/Traits/CanUse2FA.php'));
        $this->assertStringContainsString(
            'hash_equals(',
            $src,
            'CanUse2FA must compare recovery codes with hash_equals to defeat type-juggling and timing side-channel'
        );
        $this->assertStringNotContainsString(
            '== $code',
            $src,
            'No loose-equality comparison should remain'
        );
    }
}
