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

use Illuminate\Foundation\Testing\RefreshDatabase;

class SubdomainHostTestControllerTest extends \Tests\TestCase
{
    const API_URL = 'admin/subdomains_hosts';

    use RefreshDatabase;

    public function test_admin_subdomain_host_index(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $subdomainHost = \App\Models\Provisioning\SubdomainHost::create([
            'domain' => 'test.com',
        ]);
        $response = $this->actingAs($admin, 'admin')->get(self::API_URL);
        $response->assertStatus(200);
    }

    public function test_admin_subdomain_host_get(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $subdomainHost = \App\Models\Provisioning\SubdomainHost::create([
            'domain' => 'test.com',
        ]);
        $response = $this->actingAs($admin, 'admin')->get(self::API_URL.'/'.$subdomainHost->id);
        $response->assertStatus(200);
    }

    public function test_admin_subdomain_host_update(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $subdomainHost = \App\Models\Provisioning\SubdomainHost::create([
            'domain' => 'test.com',
        ]);
        $response = $this->actingAs($admin, 'admin')->put(self::API_URL.'/'.$subdomainHost->id, [
            'domain' => 'test2.com',
        ]);
        $response->assertRedirect();
    }

    public function test_admin_subdomain_host_delete(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $subdomainHost = \App\Models\Provisioning\SubdomainHost::create([
            'domain' => 'test.com',
        ]);
        $response = $this->actingAs($admin, 'admin')->delete(self::API_URL.'/'.$subdomainHost->id);
        $response->assertRedirect();
    }
}
