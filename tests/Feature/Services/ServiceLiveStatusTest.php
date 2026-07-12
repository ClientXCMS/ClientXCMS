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
        $response->assertJsonStructure(['status_badge_html']);
        $response->assertJsonMissingPath('panel_html');

        // v2.16 — html fragments must be non-empty so the JS poller can
        // swap them into the DOM. We don't assert specific tags (those
        // belong to the badge-state component tests) but require markup.
        $this->assertNotEmpty($response->json('status_badge_html'));
    }

    public function test_owner_can_request_the_refreshed_panel(): void
    {
        $customer = Customer::factory()->create();
        $service = $this->createServiceModel($customer->id);

        $response = $this->actingAs($customer, 'web')
            ->getJson(route('front.services.status', ['service' => $service, 'panel' => 1]));

        $response->assertOk()->assertJsonStructure(['status_badge_html', 'panel_html']);
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

    public function test_subuser_with_service_show_permission_can_poll(): void
    {
        $owner = Customer::factory()->create();
        $subuser = Customer::factory()->create();
        $service = $this->createServiceModel($owner->id);

        \App\Models\Account\CustomerAccountAccess::create([
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subuser->id,
            'permissions' => ['service.show'],
            'all_services' => true,
        ]);

        $response = $this->actingAs($subuser, 'web')
            ->getJson(route('front.services.status', $service));

        $response->assertOk();
        $this->assertNotEmpty($response->json('status_badge_html'));
    }

    public function test_subuser_without_service_show_permission_is_refused(): void
    {
        $owner = Customer::factory()->create();
        $subuser = Customer::factory()->create();
        $service = $this->createServiceModel($owner->id);

        \App\Models\Account\CustomerAccountAccess::create([
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subuser->id,
            'permissions' => ['invoice.show'], // unrelated permission
            'all_services' => true,
        ]);

        $response = $this->actingAs($subuser, 'web')
            ->getJson(route('front.services.status', $service));

        $response->assertNotFound();
    }
}
