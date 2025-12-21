<?php

namespace Tests\Feature\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
class SettingsTest extends \Tests\TestCase
{
    use RefreshDatabase;
    public function test_show_settings(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $response = $this->actingAs($admin, 'admin')->get(route('admin.settings.index'));
        $response->assertStatus(200);
    }
}
