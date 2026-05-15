<?php

namespace Tests\Feature\Billing;

use App\Models\Store\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponMaxUsesRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_atomic_max_uses_increment_blocks_overage(): void
    {
        $coupon = Coupon::create([
            'code' => 'PENTEST_RACE_'.uniqid(),
            'type' => 'percent',
            'applied_month' => 1,
            'free_setup' => false,
            'first_order_only' => false,
            'max_uses' => 5,
            'max_uses_per_customer' => 0,
            'usages' => 4,
            'unique_use' => 0,
            'products_required' => [],
            'is_global' => true,
            'minimum_order_amount' => 0,
        ]);

        // Two parallel attempts to bump usages past max_uses=5: only one
        // of the conditional UPDATEs may succeed.
        $a = Coupon::where('id', $coupon->id)
            ->where(function ($q) {
                $q->where('max_uses', 0)->orWhereColumn('usages', '<', 'max_uses');
            })
            ->update(['usages' => \DB::raw('usages + 1')]);
        $b = Coupon::where('id', $coupon->id)
            ->where(function ($q) {
                $q->where('max_uses', 0)->orWhereColumn('usages', '<', 'max_uses');
            })
            ->update(['usages' => \DB::raw('usages + 1')]);

        $this->assertSame(1, $a, 'first conditional update must succeed');
        $this->assertSame(0, $b, 'second conditional update must report 0 affected rows because usages now equals max_uses');
        $this->assertSame(5, (int) $coupon->fresh()->usages, 'final usages must equal max_uses, never above');
    }

    public function test_listener_uses_atomic_update_in_source(): void
    {
        $src = file_get_contents(app_path('Listeners/Store/Basket/CouponUsageListener.php'));
        $this->assertStringContainsString(
            "WhereColumn('usages', '<', 'max_uses')",
            $src,
            'CouponUsageListener must guard the increment with a (or)WhereColumn(usages, <, max_uses) clause to defeat the apply-time TOCTOU'
        );
        $this->assertStringContainsString(
            'lockForUpdate(',
            $src,
            'per-customer cap must be checked under lockForUpdate to prevent two parallel customer completions from both inserting past the limit'
        );
    }
}
