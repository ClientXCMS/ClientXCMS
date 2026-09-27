<?php

namespace Tests\Feature\Billing;

use App\Models\Store\Basket\Basket;
use App\Services\Billing\InvoiceService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CouponReservationLockTest extends TestCase
{
    use DatabaseTruncation;

    public function test_invoice_creation_waits_for_the_coupon_row_lock(): void
    {
        $customer = $this->createCustomerModel();
        $coupon = $this->createCoupon(['code' => 'LOCKED', 'max_uses' => 1, 'is_global' => true]);
        $basket = $this->createBasketForCustomer($customer);
        $basket->rows()->create(['product_id' => $this->createProductModel('active', 10)->id, 'quantity' => 1, 'billing' => 'monthly', 'currency' => 'USD']);
        $basket->update(['coupon_id' => $coupon->id]);
        $gateway = $this->createGatewayModel();

        config(['database.connections.lock_holder' => config('database.connections.'.config('database.default'))]);
        $holder = DB::connection('lock_holder');
        $holder->beginTransaction();
        $holder->table('coupons')->where('id', $coupon->id)->lockForUpdate()->first();
        $originalTimeout = (int) DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS value')->value;
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            $this->expectException(QueryException::class);
            InvoiceService::createInvoiceFromBasket(Basket::find($basket->id), $gateway);
        } finally {
            $holder->rollBack();
            DB::purge('lock_holder');
            DB::statement('SET SESSION innodb_lock_wait_timeout = '.$originalTimeout);
            $this->assertSame(0, \App\Models\Billing\Invoice::count(), 'nothing may be written while the coupon is locked elsewhere');
        }
    }

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();
        parent::tearDown();
    }

    protected function connectionsToTruncate(): array
    {
        return [config('database.default')];
    }
}
