<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
namespace Tests\Feature\Admin\Provisioning;

use App\Models\Provisioning\Server;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    const API_URL = 'admin/services';

    use RefreshDatabase;

    public function test_admin_service_index(): void
    {
        $request = $this->performAdminAction('GET', self::API_URL);
        $request->assertStatus(200);
    }

    public function test_admin_service_invalid_permission(): void
    {
        $request = $this->performAdminAction('GET', self::API_URL, [], ['admin.manage_products']);
        $request->assertStatus(403);
    }

    public function test_admin_service_valid_filter(): void
    {
        $this->seed(AdminSeeder::class);
        $response = $this->performAdminAction('GET', self::API_URL.'?filter[status]=active');
        $response->assertStatus(200);
    }

    public function test_admin_service_search(): void
    {
        $this->seed(AdminSeeder::class);
        $response = $this->performAdminAction('GET', self::API_URL.'?q=example');
        $response->assertStatus(200);
    }

    public function test_admin_service_get(): void
    {
        $this->seed(AdminSeeder::class);
        $customer = $this->createCustomerModel();
        $id = $this->createServiceModel($customer->id)->id;
        $response = $this->performAdminAction('GET', self::API_URL.'/'.$id);
        $response->assertStatus(200);
    }

    public function test_admin_service_update(): void
    {
        $this->seed(AdminSeeder::class);
        $this->seed(\Database\Seeders\ServerSeeder::class);
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel();
        $id = $this->createServiceModel($customer->id)->id;
        $response = $this->performAdminAction('PUT', self::API_URL.'/'.$id, [
            'name' => 'test 2',
            'customer_id' => $customer->id,
            'type' => 'none',
            'status' => 'active',
            'currency' => 'USD',
            'product_id' => $product->id,
            'billing' => 'monthly',
            'server_id' => Server::factory()->create()->id,
            'pricing' => [
                'monthly' => [
                    'price' => 10,
                    'setup' => 0,
                ],
            ],
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('services', [
            'id' => $id,
            'name' => 'test 2',
            'customer_id' => $customer->id,
            'type' => 'none',
            'status' => 'active',
            'currency' => 'USD',
            'product_id' => $product->id,
            'billing' => 'monthly',
        ]);
    }

    public function test_admin_service_update_pricing_with_product(): void
    {
        $this->seed(AdminSeeder::class);
        $this->seed(\Database\Seeders\ServerSeeder::class);
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel();
        $service = $this->createServiceModel($customer->id);
        $id = $service->id;
        $service->update(['product_id' => $product->id]);
        $response = $this->performAdminAction('PUT', self::API_URL.'/'.$id, [
            'name' => 'test 2',
            'customer_id' => $customer->id,
            'type' => 'none',
            'status' => 'active',
            'currency' => 'USD',
            'product_id' => $product->id,
            'billing' => 'monthly',
            'server_id' => Server::factory()->create()->id,
            'pricing' => [
                'monthly' => [
                    'price' => 10,
                    'setup' => 0,
                ],
            ],
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseMissing('pricings', [
            'related_id' => $id,
            'related_type' => 'service',
            'monthly' => 10,
            'setup_monthly' => null,
        ]);
        $this->assertDatabaseHas('pricings', [
            'related_id' => $product->id,
            'related_type' => 'product',
            'monthly' => 10,
            'setup_monthly' => null,
        ]);
        $this->assertEquals($service->refresh()->getBillingPrice()->price, 10);
        $this->assertEquals($service->refresh()->getBillingPrice()->setup, 0);
    }

    public function test_admin_service_update_pricing_with_custom_pricing()
    {
        $this->seed(AdminSeeder::class);
        $this->seed(\Database\Seeders\ServerSeeder::class);
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel();
        $service = $this->createServiceModel($customer->id);
        $id = $service->id;
        $service->update(['product_id' => null]);
        $response = $this->performAdminAction('PUT', self::API_URL.'/'.$id, [
            'name' => 'test 2',
            'customer_id' => $customer->id,
            'type' => 'none',
            'status' => 'active',
            'currency' => 'USD',
            'product_id' => 'none',
            'billing' => 'monthly',
            'server_id' => Server::factory()->create()->id,
            'pricing' => [
                'monthly' => [
                    'price' => 50,
                    'setup' => 0,
                ],
            ],
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('pricings', [
            'related_id' => $id,
            'related_type' => 'service',
            'monthly' => 50,
            'setup_monthly' => 0,
        ]);
    }

    public function test_admin_service_update_with_wrong_permission(): void
    {
        $this->seed(AdminSeeder::class);
        $this->seed(\Database\Seeders\ServerSeeder::class);
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel();
        $id = $this->createServiceModel($customer->id)->id;
        $response = $this->performAdminAction('PUT', self::API_URL.'/'.$id, [
            'name' => 'test 2',
            'customer_id' => $customer->id,
            'type' => 'none',
            'status' => 'active',
            'currency' => 'USD',
            'product_id' => $product->id,
            'billing' => 'monthly',
            'server_id' => Server::factory()->create()->id,
            'pricing' => [
                'monthly' => [
                    'price' => 10,
                    'setup' => 0,
                ],
            ],
        ], ['admin.manage_products']);
        $response->assertStatus(403);
    }

    public function test_admin_service_create_show(): void
    {
        $this->seed(AdminSeeder::class);
        $customer = $this->createCustomerModel();
        $response = $this->performAdminAction('GET', self::API_URL.'/create');
        $response->assertStatus(200);
    }

    public function test_admin_service_create_part_without_product(): void
    {
        $this->seed(AdminSeeder::class);
        $customer = $this->createCustomerModel();
        $response = $this->performAdminAction('GET', self::API_URL.'/create?customer_id='.$customer->id.'&product_id=none&type=none');
        $response->assertStatus(200);
    }

    public function test_admin_service_create_part_with_selected_customer(): void
    {
        $this->seed(AdminSeeder::class);
        $customer = $this->createCustomerModel();
        $response = $this->performAdminAction('GET', self::API_URL.'/create?customer_id='.$customer->id);
        $response->assertStatus(200);
        $response->assertSee($customer->first_name);
    }

    public function test_admin_service_create_part_with_product(): void
    {
        $this->seed(AdminSeeder::class);
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel();
        $response = $this->performAdminAction('GET', self::API_URL.'/create?customer_id='.$customer->id);
        $response->assertStatus(200);
        $response = $this->performAdminAction('GET', self::API_URL.'/create?customer_id='.$customer->id.'&product_id='.$product->id.'&type=none');
        $response->assertStatus(200);
    }

    public function test_admin_service_store_without_product(): void
    {
        $this->seed(AdminSeeder::class);
        $customer = $this->createCustomerModel();
        $response = $this->performAdminAction('POST', self::API_URL, [
            'name' => 'test 2',
            'customer_id' => $customer->id,
            'type' => 'none',
            'status' => 'active',
            'currency' => 'USD',
            'product_id' => 'none',
            'billing' => 'monthly',
            'server_id' => Server::factory()->create()->id,
            'create' => true,
            'pricing' => [
                'monthly' => [
                    'price' => 10,
                    'setup' => 0,
                ],
            ],
        ]);
        $this->assertDatabaseHas('services', [
            'name' => 'test 2',
            'customer_id' => $customer->id,
            'type' => 'none',
            'status' => 'pending',
            'currency' => 'USD',
            'product_id' => null,
            'billing' => 'monthly',
        ]);
        $service = \App\Models\Provisioning\Service::where('name', 'test 2')->first();

        $this->assertDatabaseHas('pricings', [
            'related_id' => $service->id,
            'related_type' => 'service',
            'monthly' => 10,
            'setup_monthly' => 0,
        ]);
    }

    public function test_admin_service_store_with_product(): void
    {
        $this->seed(AdminSeeder::class);
        $customer = $this->createCustomerModel();
        $product = $this->createProductModel();
        $response = $this->performAdminAction('POST', self::API_URL, [
            'name' => 'test 2',
            'customer_id' => $customer->id,
            'type' => 'none',
            'status' => 'active',
            'currency' => 'USD',
            'product_id' => $product->id,
            'billing' => 'monthly',
            'create' => true,
            'server_id' => Server::factory()->create()->id,
            'pricing' => [
                'monthly' => [
                    'price' => 10,
                    'setup' => 0,
                ],
            ],
        ]);
        $this->assertDatabaseHas('services', [
            'name' => 'test 2',
            'customer_id' => $customer->id,
            'type' => 'none',
            'status' => 'pending',
            'currency' => 'USD',
            'product_id' => $product->id,
            'billing' => 'monthly',
        ]);
        $service = \App\Models\Provisioning\Service::where('name', 'test 2')->first();

        $this->assertDatabaseHas('pricings', [
            'related_id' => $service->id,
            'related_type' => 'service',
            'monthly' => 10,
            'setup_monthly' => 0,
        ]);
    }

    public function test_admin_service_show_no_product(): void
    {
        $this->seed(AdminSeeder::class);
        $customer = $this->createCustomerModel();
        $service = $this->createServiceModel($customer->id);
        $response = $this->performAdminAction('GET', self::API_URL.'/'.$service->id);
        $response->assertStatus(200);
    }
}
