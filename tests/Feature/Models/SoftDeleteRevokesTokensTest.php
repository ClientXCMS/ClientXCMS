<?php

namespace Tests\Feature\Models;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteRevokesTokensTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_soft_delete_drops_all_api_tokens(): void
    {
        $customer = Customer::factory()->create();
        $customer->createToken('api', ['client-api']);
        $this->assertSame(1, $customer->tokens()->count());

        $customer->delete();

        $this->assertSame(0, $customer->tokens()->count(), 'Soft-deleting a customer must invalidate every Sanctum token they own');
    }

    public function test_admin_soft_delete_drops_all_api_tokens(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = Admin::first();
        $admin->createToken('pentest', ['*']);
        $this->assertSame(1, $admin->tokens()->count());

        $admin->delete();

        $this->assertSame(0, $admin->tokens()->count(), 'Soft-deleting an admin must invalidate every Sanctum token they own');
    }
}
