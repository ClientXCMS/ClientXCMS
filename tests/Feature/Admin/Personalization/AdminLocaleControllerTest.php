<?php

namespace Admin\Personalization;

class AdminLocaleControllerTest extends \Tests\TestCase
{
    public function test_admin_locale_index(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $response = $this->actingAs($admin, 'admin')->get(route('admin.locales.index'));
        $response->assertStatus(200);
    }

    public function test_admin_locale_update(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $response = $this->actingAs($admin, 'admin')->post(route('admin.locales.download', ['locale' => 'es_ES']));
        $response->assertStatus(302);
    }

    public function test_admin_locale_toggle(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $response = $this->actingAs($admin, 'admin')->post(route('admin.locales.toggle', ['locale' => 'es_ES']));
        $response->assertStatus(302);
    }

    public function test_admin_locale_toggle_not_downloaded(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $response = $this->actingAs($admin, 'admin')->post(route('admin.locales.toggle', ['locale' => 'aaa']));
        $response->assertNotFound();
    }
}
