<?php

namespace Tests\Feature\Models;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class TwoFactorSecretEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'JBSWY3DPEHPK3PXP';

    public function test_the_totp_secret_is_encrypted_at_rest(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable(self::SECRET);

        $stored = DB::table('metadata')
            ->where('model_type', Customer::class)
            ->where('model_id', $customer->id)
            ->where('key', '2fa_secret')
            ->value('value');

        $this->assertNotEmpty($stored);
        $this->assertStringNotContainsString(
            self::SECRET,
            $stored,
            'The TOTP secret must not be readable from the table: it is enough to generate the codes of the account it protects'
        );
        $this->assertSame(self::SECRET, Crypt::decryptString($stored));
    }

    public function test_the_secret_reads_back_in_clear_for_the_application(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable(self::SECRET);

        $this->assertSame(self::SECRET, $customer->fresh()->twoFactorSecret());
    }

    public function test_a_code_still_validates_once_the_secret_is_encrypted(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable(self::SECRET);
        $code = (new Google2FA)->getCurrentOtp(self::SECRET);

        $this->assertTrue($customer->fresh()->isValidate2FA($code));
        $this->assertTrue($customer->fresh()->verifyDeviceFactor($code));
        $this->assertTrue($customer->fresh()->twoFactorEnabled());
    }

    public function test_a_secret_stored_before_encryption_still_validates(): void
    {
        $customer = Customer::factory()->create();
        $customer->attachMetadata('2fa_secret', self::SECRET);
        $code = (new Google2FA)->getCurrentOtp(self::SECRET);

        $this->assertSame(self::SECRET, $customer->fresh()->twoFactorSecret(), 'Secrets written before the fix must keep working');
        $this->assertTrue($customer->fresh()->isValidate2FA($code));
    }

    public function test_an_account_without_two_factor_has_no_secret(): void
    {
        $customer = Customer::factory()->create();

        $this->assertNull($customer->twoFactorSecret());
        $this->assertFalse($customer->isValidate2FA('123456'));
    }

    public function test_disabling_two_factor_drops_the_secret(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable(self::SECRET);

        $customer->twoFactorDisable();

        $this->assertNull($customer->fresh()->twoFactorSecret());
        $this->assertFalse($customer->fresh()->twoFactorEnabled());
    }

    public function test_the_migration_encrypts_the_secrets_already_stored(): void
    {
        $customer = Customer::factory()->create();
        $customer->attachMetadata('2fa_secret', self::SECRET);

        $this->runEncryptionMigration();

        $stored = $customer->fresh()->getMetadata('2fa_secret');
        $this->assertStringNotContainsString(self::SECRET, $stored);
        $this->assertSame(self::SECRET, Crypt::decryptString($stored));
        $this->assertSame(self::SECRET, $customer->fresh()->twoFactorSecret());
    }

    public function test_replaying_the_migration_does_not_encrypt_twice(): void
    {
        $customer = Customer::factory()->create();
        $customer->attachMetadata('2fa_secret', self::SECRET);

        $this->runEncryptionMigration();
        $this->runEncryptionMigration();

        $this->assertSame(self::SECRET, $customer->fresh()->twoFactorSecret());
    }

    private function runEncryptionMigration(): void
    {
        (require database_path('migrations/2026_09_15_000002_encrypt_existing_two_factor_secrets.php'))->up();
    }
}
