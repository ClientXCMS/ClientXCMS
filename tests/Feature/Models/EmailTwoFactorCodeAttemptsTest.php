<?php

namespace Tests\Feature\Models;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Locks the failure-counter contract on the email 2FA code path.
 *
 * Without a counter, a 6-digit code valid for 5 minutes and reusable until
 * expiry is bruteforce-tractable: 900k pool, attacker can keep guessing
 * within the validity window. The counter clamps the attempt budget to a
 * small fixed number so the probability of guessing stays negligible
 * regardless of how often the attacker hits the verify endpoint.
 */
class EmailTwoFactorCodeAttemptsTest extends TestCase
{
    use RefreshDatabase;

    private const MAX_ATTEMPTS = 5;

    public function test_email_code_is_invalidated_after_five_failed_attempts(): void
    {
        $customer = $this->customerWithActiveEmailCode('123456');

        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $this->assertFalse(
                $customer->isValidEmailTwoFactorCode('999999'),
                "Wrong code on attempt #{$i} must return false"
            );
        }

        $this->assertFalse(
            $customer->fresh()->isValidEmailTwoFactorCode('123456'),
            'Correct code after lockout must still be rejected - the attacker would otherwise just brute force across 5 attempts then submit the guessed value'
        );

        $customer = $customer->fresh();
        $this->assertNull(
            $customer->getMetadata('2fa_email_code'),
            'Locked-out code must be detached so the user can request a fresh one'
        );
        $this->assertNull(
            $customer->getMetadata('2fa_email_code_expires_at'),
            'Expiry metadata must be cleared alongside the hash'
        );
    }

    public function test_correct_code_succeeds_within_attempt_budget(): void
    {
        $customer = $this->customerWithActiveEmailCode('123456');

        for ($i = 0; $i < self::MAX_ATTEMPTS - 1; $i++) {
            $customer->isValidEmailTwoFactorCode('999999');
        }

        $this->assertTrue(
            $customer->fresh()->isValidEmailTwoFactorCode('123456'),
            'A legitimate user mistyping under the budget must still succeed'
        );
    }

    public function test_attempt_counter_resets_after_successful_verify(): void
    {
        $customer = $this->customerWithActiveEmailCode('111111');

        $customer->isValidEmailTwoFactorCode('999999');
        $customer->isValidEmailTwoFactorCode('999999');
        $this->assertTrue($customer->fresh()->isValidEmailTwoFactorCode('111111'));

        // New cycle: full budget must be available again.
        $customer = $customer->fresh();
        $customer->attachMetadata('2fa_email_code', Hash::make('222222'));
        $customer->attachMetadata('2fa_email_code_expires_at', now()->addMinutes(5)->toDateTimeString());

        for ($i = 0; $i < self::MAX_ATTEMPTS - 1; $i++) {
            $customer->isValidEmailTwoFactorCode('999999');
        }
        $this->assertTrue(
            $customer->fresh()->isValidEmailTwoFactorCode('222222'),
            'Counter must reset on success - otherwise a legitimate user who mistyped twice yesterday gets only 3 attempts today'
        );
    }

    public function test_no_attempt_consumed_when_no_active_code(): void
    {
        $customer = Customer::factory()->create();

        for ($i = 0; $i < 100; $i++) {
            $this->assertFalse($customer->isValidEmailTwoFactorCode('999999'));
        }

        // Counter should not have been touched at all.
        $this->assertNull($customer->fresh()->getMetadata('2fa_email_code_attempts'));
    }

    public function test_expired_code_resets_counter_without_consuming_attempt(): void
    {
        $customer = $this->customerWithActiveEmailCode('123456');
        $customer->isValidEmailTwoFactorCode('999999'); // counter = 1
        $customer->attachMetadata('2fa_email_code_expires_at', now()->subMinutes(1)->toDateTimeString());

        $this->assertFalse(
            $customer->fresh()->isValidEmailTwoFactorCode('123456'),
            'Expired code must reject regardless of value'
        );

        $customer->attachMetadata('2fa_email_code', Hash::make('654321'));
        $customer->attachMetadata('2fa_email_code_expires_at', now()->addMinutes(5)->toDateTimeString());

        for ($i = 0; $i < self::MAX_ATTEMPTS - 1; $i++) {
            $customer->isValidEmailTwoFactorCode('999999');
        }
        $this->assertTrue(
            $customer->fresh()->isValidEmailTwoFactorCode('654321'),
            'Expiry cleanup must reset the counter - otherwise the old attempts haunt the new code'
        );
    }

    private function customerWithActiveEmailCode(string $code): Customer
    {
        $customer = Customer::factory()->create();
        $customer->attachMetadata('2fa_email_code', Hash::make($code));
        $customer->attachMetadata('2fa_email_code_expires_at', now()->addMinutes(5)->toDateTimeString());

        return $customer;
    }
}
