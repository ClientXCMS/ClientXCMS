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
namespace Database\Seeders;

use App\Models\Admin\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = file_get_contents(resource_path('roles.json'));
        $roles = json_decode($roles, true);
        if (Role::count() > 0) {
            return;
        }
        foreach ($roles as $role) {
            $permissions = $role['permissions'];
            $tmp = [];
            unset($role['permissions']);
            $role = \App\Models\Admin\Role::updateOrCreate($role);
            foreach ($permissions as $permission) {
                $permission = \App\Models\Admin\Permission::where('name', $permission)->first();
                if ($permission) {
                    $tmp[] = $permission->id;
                }
            }
            $role->permissions()->sync($tmp);
        }
    }
}
