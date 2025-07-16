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

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = file_get_contents(resource_path('permissions.json'));
        $permissions = json_decode($permissions, true);
        $extensions = app('extension')->getAllExtensions(false, true);
        foreach ($extensions as $extension) {
            if (! in_array($extension->type, ['modules', 'addons'])) {
                continue;
            }
            $path = $extension->type.'/'.$extension->uuid.'/permissions.json';
            if (file_exists(base_path($path))) {
                $extensionPermissions = file_get_contents(base_path($path));
                $extensionPermissions = json_decode($extensionPermissions, true);
                if (is_array($extensionPermissions)) {
                    $permissions = array_merge($permissions, $extensionPermissions);
                }
            }
        }
        foreach ($permissions as $permission) {

            \App\Models\Admin\Permission::updateOrCreate($permission);
        }
    }
}
