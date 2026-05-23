<?php

namespace Tests\Feature\Services;

use App\Models\Account\Customer;
use Database\Seeders\GatewaySeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceLiveStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StoreSeeder::class);
        $this->seed(GatewaySeeder::class);
    }

    public function test_owner_receives_a_status_snapshot(): void
    {
        $customer = Customer::factory()->create();
        $service = $this->createServiceModel($customer->id);

        $response = $this->actingAs($customer, 'web')
            ->getJson(route('front.services.status', $service));

        $response->assertOk();
        $response->assertJsonStructure([
            'uuid', 'status', 'state', 'expires_at', 'days_to_renewal', 'last_check', 'usage_estimate',
        ]);
        $this->assertSame($service->uuid, $response->json('uuid'));
    }

    public function test_other_customer_cannot_poll_my_service(): void
    {
        $owner = Customer::factory()->create();
        $stranger = Customer::factory()->create();
        $service = $this->createServiceModel($owner->id);

        $response = $this->actingAs($stranger, 'web')
            ->getJson(route('front.services.status', $service));

        $response->assertNotFound();
    }

    public function test_anonymous_caller_is_redirected_to_login(): void
    {
        $owner = Customer::factory()->create();
        $service = $this->createServiceModel($owner->id);

        $response = $this->get(route('front.services.status', $service));

        // 'auth' middleware on the parent group redirects to /login
        $this->assertContains($response->status(), [302, 401]);
    }
}
