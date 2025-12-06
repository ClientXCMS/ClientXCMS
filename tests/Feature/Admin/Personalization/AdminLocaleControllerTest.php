<?php

namespace Admin\Personalization;

use App\Models\Admin\Setting;
use App\Services\Core\LocaleService;
use Illuminate\Support\Facades\Cache;

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

    public function test_admin_locale_update_not_existing(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $response = $this->actingAs($admin, 'admin')->post(route('admin.locales.download', ['locale' => 'aaa']));
        $response->assertNotFound();
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

    public function test_admin_locale_toggle_not_enabled(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $response = $this->actingAs($admin, 'admin')->post(route('admin.locales.toggle', ['locale' => 'en_GB']));
        $response->assertRedirect();
    }

    public function setUp(): void
    {
        parent::setUp();
        Setting::where('name', 'default_enabled_locales')->delete();
        Cache::forget('locales');
    }
}
