<?php

namespace Tests\Feature\Models;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pins the cross-cycle brute-force defense.
 *
 * F5 caps a single email code to 5 guesses. Without further limits, an
 * attacker could chain (5 fails -> code wiped -> auto-resend -> 5 fails)
 * indefinitely, accumulating roughly 15 attempts per minute against the
 * 900k pool. F1.4 adds a soft global cap of 3 burned cycles before the
 * mailbox is silenced for 5 minutes, capping the total budget to ~15
 * guesses per 15 minutes (~2.4e-5 chance of a hit per cooldown window).
 *
 * The window resets on successful verification and after the cooldown
 * elapses, so a real user who fat-fingered the code twice yesterday is
 * not punished today.
 */
class EmailTwoFactorCooldownTest extends TestCase
{
    use RefreshDatabase;

    private const MAX_ATTEMPTS = 5;
    private const MAX_CYCLES = 3;

    public function test_burning_three_full_cycles_blocks_further_sends(): void
    {
        $customer = Customer::factory()->create();

        for ($cycle = 0; $cycle < self::MAX_CYCLES; $cycle++) {
            $this->seedActiveCode($customer, '123456');
            for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
                $customer->isValidEmailTwoFactorCode('999999');
            }
        }

        $this->assertTrue(
            $customer->fresh()->isEmailTwoFactorOnCooldown(),
            'After burning the max number of cycles the user must be on cooldown'
        );

        $this->expectsNoNewEmailCode($customer);
    }

    public function test_cooldown_lifts_after_five_minutes(): void
    {
        $customer = Customer::factory()->create();
        Carbon::setTestNow('2026-05-24 10:00:00');

        for ($cycle = 0; $cycle < self::MAX_CYCLES; $cycle++) {
            $this->seedActiveCode($customer, '123456');
            for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
                $customer->isValidEmailTwoFactorCode('999999');
            }
        }

        $this->assertTrue($customer->fresh()->isEmailTwoFactorOnCooldown());

        Carbon::setTestNow('2026-05-24 10:05:01');

        $this->assertFalse(
            $customer->fresh()->isEmailTwoFactorOnCooldown(),
            'Cooldown must elapse 5 minutes after the last burned cycle'
        );
    }

    public function test_successful_verify_resets_burned_counter(): void
    {
        $customer = Customer::factory()->create();

        // Burn 2 cycles (under the cap)
        for ($cycle = 0; $cycle < 2; $cycle++) {
            $this->seedActiveCode($customer, '123456');
            for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
                $customer->isValidEmailTwoFactorCode('999999');
            }
        }

        // Then succeed on the 3rd cycle
        $this->seedActiveCode($customer, '777777');
        $this->assertTrue($customer->fresh()->isValidEmailTwoFactorCode('777777'));

        $customer = $customer->fresh();
        $this->assertNull(
            $customer->getMetadata('2fa_email_burned_cycles'),
            'Successful verify must clear the burned counter so the next session starts fresh'
        );
        $this->assertFalse($customer->isEmailTwoFactorOnCooldown());
    }

    public function test_under_cap_does_not_trigger_cooldown(): void
    {
        $customer = Customer::factory()->create();

        for ($cycle = 0; $cycle < self::MAX_CYCLES - 1; $cycle++) {
            $this->seedActiveCode($customer, '123456');
            for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
                $customer->isValidEmailTwoFactorCode('999999');
            }
        }

        $this->assertFalse($customer->fresh()->isEmailTwoFactorOnCooldown());
    }

    private function seedActiveCode(Customer $customer, string $code): void
    {
        $customer->attachMetadata('2fa_email_code', Hash::make($code));
        $customer->attachMetadata('2fa_email_code_expires_at', now()->addMinutes(5)->toDateTimeString());
    }

    private function expectsNoNewEmailCode(Customer $customer): void
    {
        $customer = $customer->fresh();
        // Ensure no active code is lingering from the last burned cycle
        $this->assertNull($customer->getMetadata('2fa_email_code'));

        $customer->sendTwoFactorEmailCode('web', '127.0.0.1');

        $this->assertNull(
            $customer->fresh()->getMetadata('2fa_email_code'),
            'sendTwoFactorEmailCode must no-op while the user is on cooldown'
        );
    }
}
