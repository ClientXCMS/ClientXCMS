<?php

namespace Tests\Feature\Admin\Provisioning;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

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
        $admin = $this->createAdminModel();;
        $subdomainHost = \App\Models\Provisioning\SubdomainHost::create([
            'domain' => 'test.com',
        ]);
        $response = $this->performAdminAction('GET', self::API_URL.'/'.$subdomainHost->id);
        $response->assertStatus(200);
    }

    public function test_admin_subdomain_host_update(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = $this->createAdminModel();;
        $subdomainHost = \App\Models\Provisioning\SubdomainHost::create([
            'domain' => 'test.com',
        ]);
        $response = $this->performAdminAction('PUT', self::API_URL.'/'.$subdomainHost->id, [
            'domain' => 'test2.com',
        ]);
        $response->assertRedirect();
    }

    public function test_admin_subdomain_host_delete(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = $this->createAdminModel();;
        $subdomainHost = \App\Models\Provisioning\SubdomainHost::create([
            'domain' => 'test.com',
        ]);
        $response = $this->performAdminAction('DELETE', self::API_URL.'/'.$subdomainHost->id);
        $response->assertRedirect();
    }
}
