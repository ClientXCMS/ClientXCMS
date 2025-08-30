<?php

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
