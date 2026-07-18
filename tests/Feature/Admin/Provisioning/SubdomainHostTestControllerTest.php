<?php

namespace Tests\Feature\Admin\Provisioning;

use App\Models\Provisioning\SubdomainHost;
use App\Models\Store\Group;
use App\Models\Store\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubdomainHostTestControllerTest extends \Tests\TestCase
{
    const API_URL = 'admin/subdomains_hosts';

    use RefreshDatabase;

    public function test_admin_subdomain_host_index(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = $this->createAdminModel();
        $subdomainHost = \App\Models\Provisioning\SubdomainHost::create([
            'domain' => 'test.com',
        ]);
        $response = $this->performAdminAction('GET', route('admin.subdomains_hosts.index'));
        $response->assertStatus(200);
    }

    public function test_admin_subdomain_host_get(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = $this->createAdminModel();
        $subdomainHost = \App\Models\Provisioning\SubdomainHost::create([
            'domain' => 'test.com',
        ]);
        $response = $this->performAdminAction('GET', self::API_URL.'/'.$subdomainHost->id);
        $response->assertStatus(200);
    }

    public function test_admin_subdomain_host_update(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = $this->createAdminModel();
        $product = Product::factory()->create(['status' => 'active']);
        $group = Group::factory()->create(['status' => 'active']);
        $subdomainHost = SubdomainHost::create([
            'domain' => 'test.com',
        ]);
        $response = $this->performAdminAction('PUT', self::API_URL.'/'.$subdomainHost->id, [
            'domain' => 'test2.com',
            'products' => [$product->id],
            'groups' => [$group->id],
        ]);
        $response->assertRedirect();
        $subdomainHost->refresh();
        $this->assertSame([$product->id], $subdomainHost->products);
        $this->assertSame([$group->id], $subdomainHost->groups);
    }

    public function test_admin_subdomain_host_delete(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = $this->createAdminModel();
        $subdomainHost = \App\Models\Provisioning\SubdomainHost::create([
            'domain' => 'test.com',
        ]);
        $response = $this->performAdminAction('DELETE', self::API_URL.'/'.$subdomainHost->id);
        $response->assertRedirect();
    }

    public function test_admin_subdomain_host_store_restrictions(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = $this->createAdminModel();
        $product = Product::factory()->create(['status' => 'active']);
        $group = Group::factory()->create(['status' => 'active']);

        $response = $this->performAdminAction('POST', self::API_URL, [
            'domain' => 'restricted.test',
            'products' => [$product->id],
            'groups' => [$group->id],
        ]);

        $response->assertRedirect();
        $subdomainHost = SubdomainHost::where('domain', 'restricted.test')->firstOrFail();
        $this->assertSame([$product->id], $subdomainHost->products);
        $this->assertSame([$group->id], $subdomainHost->groups);
    }
}
