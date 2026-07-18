<?php

namespace Tests\Feature\Admin\Core;

use App\Models\Admin\Admin;
use App\Models\Admin\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): Admin
    {
        $role = Role::where('is_default', true)->first() ?? Role::firstOrCreate(['name' => 'pentest-role'], ['level' => 1, 'is_admin' => false, 'is_default' => false]);

        return Admin::create([
            'firstname' => 'pentest',
            'lastname' => 'staff',
            'username' => 'pentest-staff',
            'email' => 'pentest-staff@example.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
            'locale' => 'fr_FR',
        ]);
    }

    public function test_update_form_request_blocks_admin_without_permission(): void
    {
        $staff = $this->staff();
        $response = $this->performAdminAction(
            'PUT',
            route('admin.staffs.update', $staff),
            ['firstname' => 'x', 'lastname' => 'y', 'email' => 'x@x.com', 'role_id' => $staff->role_id, 'locale' => 'fr_FR'],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }
}
