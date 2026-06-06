<?php

namespace Tests\Feature\Admin\Personalization;

use App\Helpers\Countries;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AdminLocaleControllerTest extends \Tests\TestCase
{
    use RefreshDatabase;

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

    public function test_admin_can_update_enabled_countries(): void
    {
        Storage::fake('local');
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.locales.countries'), [
            'countries' => ['FR', 'BE'],
        ]);

        $response->assertRedirect();
        Storage::assertExists('enabled_countries.json');
        $this->assertSame(['FR', 'BE'], json_decode(Storage::get('enabled_countries.json'), true));
        $this->assertSame(['BE' => 'Belgium', 'FR' => 'France'], Countries::names());
    }

    public function test_admin_without_manage_settings_cannot_update_countries(): void
    {
        // L1 of the v2.16 audit. countries() was the only method on this
        // controller missing the staff_aborts_permission check, so a
        // staff member without MANAGE_SETTINGS could silently rewrite the
        // geo-list and break the sales funnel.
        Storage::fake('local');

        // performAdminAction with non-* permissions strips the super-admin
        // role and applies the (empty) sync, so MANAGE_SETTINGS is absent.
        $response = $this->performAdminAction(
            'POST',
            route('admin.locales.countries'),
            ['countries' => ['FR']],
            ['some_other_permission']
        );

        $response->assertForbidden();
        Storage::assertMissing('enabled_countries.json');
    }

    public function test_countries_use_limited_default_enabled_list(): void
    {
        Storage::fake('local');

        $this->assertArrayHasKey('FR', Countries::names());
        $this->assertArrayHasKey('BE', Countries::names());
        $this->assertArrayNotHasKey('AF', Countries::names());
        $this->assertCount(20, Countries::enabledCodes());
    }

    protected function setUp(): void
    {
        parent::setUp();
        Setting::where('name', 'default_enabled_locales')->delete();
        Cache::forget('locales');
    }
}
