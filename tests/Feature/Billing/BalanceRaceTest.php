<?php

namespace Tests\Feature\Billing;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_try_deduct_balance_succeeds_with_sufficient_funds(): void
    {
        $customer = Customer::factory()->create(['balance' => 200]);
        $this->assertTrue($customer->tryDeductBalance(150));
        $this->assertEqualsWithDelta(50, $customer->fresh()->balance, 0.001);
    }

    public function test_try_deduct_balance_rejects_insufficient_funds(): void
    {
        $customer = Customer::factory()->create(['balance' => 100]);
        $this->assertFalse($customer->tryDeductBalance(150));
        $this->assertEqualsWithDelta(100, $customer->fresh()->balance, 0.001, 'Balance must stay unchanged when debit is refused');
    }

    public function test_try_deduct_balance_is_race_safe(): void
    {
        // Simulate two parallel debit attempts on the same row by querying
        // through TWO independent model instances pointing at the same id.
        $row = Customer::factory()->create(['balance' => 200]);
        $a = Customer::find($row->id);
        $b = Customer::find($row->id);

        $okA = $a->tryDeductBalance(150);
        $okB = $b->tryDeductBalance(150);

        $this->assertTrue($okA, 'first debit must succeed');
        $this->assertFalse($okB, 'second debit on the same row must fail because the atomic UPDATE WHERE balance >= 150 no longer matches');
        $this->assertEqualsWithDelta(50, $row->fresh()->balance, 0.001, 'Final balance must equal 200 - 150, not 200 - 300');
    }

    public function test_try_deduct_balance_refuses_zero_or_negative(): void
    {
        $customer = Customer::factory()->create(['balance' => 100]);
        $this->assertFalse($customer->tryDeductBalance(0));
        $this->assertFalse($customer->tryDeductBalance(-10));
        $this->assertEqualsWithDelta(100, $customer->fresh()->balance, 0.001);
    }
}
