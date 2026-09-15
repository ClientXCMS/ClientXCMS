<?php

namespace Tests\Feature\Admin\Core;

use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLayoutPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_a_vertical_layout_preference(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = Admin::firstOrFail();

        $response = $this->actingAs($admin, 'admin')->put(route('admin.staffs.profile'), $this->profileData($admin, [
            'admin_layout' => Admin::LAYOUT_VERTICAL,
        ]));

        $response->assertRedirect();
        $this->assertSame(Admin::LAYOUT_VERTICAL, $admin->fresh()->admin_layout);
        $this->assertTrue($admin->fresh()->usesVerticalLayout());
    }

    public function test_unknown_admin_layout_is_rejected(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = Admin::firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.staffs.profile'))
            ->put(route('admin.staffs.profile'), $this->profileData($admin, ['admin_layout' => 'unknown']));

        $response->assertRedirect(route('admin.staffs.profile'));
        $response->assertSessionHasErrors('admin_layout');
        $this->assertSame(Admin::LAYOUT_HORIZONTAL, $admin->fresh()->admin_layout);
    }

    public function test_layout_preferences_are_isolated_between_admins(): void
    {
        $this->seed(AdminSeeder::class);
        $verticalAdmin = Admin::firstOrFail();
        $horizontalAdmin = Admin::factory()->create();

        $this->actingAs($verticalAdmin, 'admin')->put(route('admin.staffs.profile'), $this->profileData($verticalAdmin, [
            'admin_layout' => Admin::LAYOUT_VERTICAL,
        ]))->assertRedirect();

        $this->assertSame(Admin::LAYOUT_VERTICAL, $verticalAdmin->fresh()->admin_layout);
        $this->assertSame(Admin::LAYOUT_HORIZONTAL, $horizontalAdmin->fresh()->admin_layout);
    }

    public function test_quick_toggle_switches_between_both_layouts(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = Admin::firstOrFail();

        $this->actingAs($admin, 'admin')->post(route('admin.profile.layout.toggle'))->assertRedirect();
        $this->assertSame(Admin::LAYOUT_VERTICAL, $admin->fresh()->admin_layout);

        $this->post(route('admin.profile.layout.toggle'))->assertRedirect();
        $this->assertSame(Admin::LAYOUT_HORIZONTAL, $admin->fresh()->admin_layout);
    }

    public function test_prompt_choice_is_saved_and_prompt_is_marked_as_seen(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = Admin::firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['show_admin_layout_prompt' => true])
            ->post(route('admin.profile.layout.choose'), ['admin_layout' => Admin::LAYOUT_VERTICAL])
            ->assertRedirect()
            ->assertSessionMissing('show_admin_layout_prompt');

        $admin->refresh();
        $this->assertSame(Admin::LAYOUT_VERTICAL, $admin->admin_layout);
        $this->assertNotNull($admin->admin_layout_prompted_at);
    }

    private function profileData(Admin $admin, array $overrides = []): array
    {
        return array_merge([
            'firstname' => $admin->firstname,
            'lastname' => $admin->lastname,
            'email' => $admin->email,
            'username' => $admin->username,
            'signature' => $admin->signature,
            'locale' => $admin->locale,
            'admin_layout' => Admin::LAYOUT_HORIZONTAL,
        ], $overrides);
    }
}
