<?php

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
