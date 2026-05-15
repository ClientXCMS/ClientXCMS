<?php

namespace Tests\Feature\Admin\Store;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponRequestRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_form_request_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.coupons.store'),
            ['code' => 'PENTEST'],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_store_form_request_allows_admin_with_manage_coupons(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.coupons.store'),
            [],
            ['admin.manage_coupons']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_COUPONS must not be blocked');
    }
}
