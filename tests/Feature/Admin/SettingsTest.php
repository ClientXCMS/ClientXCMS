<?php

namespace Admin;

class SettingsTest extends \Tests\TestCase
{
    public function show_settings(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::first();
        $response = $this->actingAs($admin, 'admin')->get(route('admin.settings'));
        $response->assertStatus(200);
    }
}
