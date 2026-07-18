<?php

namespace Tests\Feature\Api\Customers;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_method_accepts_request_argument(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Api\Customers\CustomerController::class, 'action');
        $params = $reflection->getParameters();

        $hasRequest = false;
        foreach ($params as $p) {
            $type = $p->getType();
            if ($type instanceof \ReflectionNamedType && $type->getName() === \Illuminate\Http\Request::class) {
                $hasRequest = true;
                break;
            }
        }
        $this->assertTrue(
            $hasRequest,
            'CustomerController::action() must inject Illuminate\\Http\\Request - it dereferences $request->reason / $request->notify / $request->force'
        );
    }

    public function test_action_endpoint_does_not_500_on_admin_call(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = Admin::first();
        $customer = Customer::factory()->create();

        $token = $admin->createToken('pentest', ['*'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->postJson('/api/application/customers/'.$customer->id.'/action/disable2FA', []);

        $this->assertNotSame(500, $response->status(), 'action endpoint must not crash with undefined variable error');
    }
}
