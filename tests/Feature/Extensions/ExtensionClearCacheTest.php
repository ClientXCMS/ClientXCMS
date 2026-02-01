<?php

namespace Tests\Feature\Extensions;

use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionClearCacheTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndGetAdmin(): Admin
    {
        $this->seed(AdminSeeder::class);

        return Admin::first();
    }

    // Unauthenticated requests are blocked by the admin middleware (401)
    // before reaching staff_aborts_permission (which would return 403).
    public function test_clear_cache_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.settings.extensions.clear-cache'));

        $response->assertStatus(401);
    }

    public function test_clear_cache_returns_json_when_ajax(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.clear-cache'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'errors',
        ]);
    }

    public function test_clear_cache_returns_redirect_when_not_ajax(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.extensions.clear-cache'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_existing_clear_route_still_works(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.extensions.clear'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_existing_clear_route_returns_json_when_ajax(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.clear'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'errors',
        ]);
    }
}
