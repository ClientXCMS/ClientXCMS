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

use App\Models\Admin\Admin;
use App\Models\Admin\Role;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Admin::count() > 0) {
            return;
        }
        Admin::insert([
            'email' => 'admin@localhost',
            'password' => \Hash::make('password'),
            'firstname' => 'Admin',
            'lastname' => 'Admin',
            'username' => 'Admin',
            'last_login' => now(),
            'last_login_ip' => '',
            'signature' => 'Plop',
            'role_id' => Role::factory()->create()->id,
        ]);
    }
}
