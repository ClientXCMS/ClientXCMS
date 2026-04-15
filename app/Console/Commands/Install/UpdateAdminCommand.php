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
use Illuminate\Console\Command;

class UpdateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:update-admin {id} {--email=} {--password=} {--firstname=} {--lastname=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the admin user for the clientxcms application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        $admin = Admin::find($id);

        if (! $admin) {
            $this->error("Admin with ID {$id} not found.");

            return;
        }

        $data = [];

        if ($this->option('firstname')) {
            $data['firstname'] = $this->option('firstname');
        } else {
            $data['firstname'] = $this->ask('Admin firstname', $admin->firstname);
        }

        if ($this->option('lastname')) {
            $data['lastname'] = $this->option('lastname');
        } else {
            $data['lastname'] = $this->ask('Admin lastname', $admin->lastname);
        }

        if ($this->option('email')) {
            $data['email'] = $this->option('email');
        } else {
            $data['email'] = $this->ask('Admin email', $admin->email);
        }

        if ($this->option('password')) {
            $data['password'] = bcrypt($this->option('password'));
        }

        $admin->update($data);
        $this->info('Admin user updated successfully.');
    }
}
