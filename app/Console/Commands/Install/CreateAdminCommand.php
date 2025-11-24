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
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Console\Commands\Install;

use App\Models\Admin\Admin;
use App\Models\Admin\Role;
use Illuminate\Console\Command;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:install-admin {--email=} {--password=} {--firstname=} {--lastname=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the admin user for the clientxcms application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('firstname') && $this->option('lastname')) {
            $username = $this->option('firstname').' '.$this->option('lastname');
        } else {
            $username = $this->ask('Admin username');
        }
        $role = Role::where('is_default', true)->first();
        if (!$role) {
            $this->error('No default role found. Please create a role first.');
            return;
        }
        Admin::insert([
            'username' => $username,
            'email' => $this->option('email') ?? $this->ask('Admin email'),
            'password' => bcrypt($this->option('password') ?? $this->secret('Admin password')),
            'firstname' => $this->option('firstname') ?? $this->ask('Admin firstname'),
            'lastname' => $this->option('lastname') ?? $this->ask('Admin lastname'),
            'role_id' => $role->id,
        ]);
        $this->info('Admin user created successfully.');
    }
}
