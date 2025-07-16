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
namespace App\Console\Commands\Install;

use App\Models\Admin\Admin;
use Illuminate\Console\Command;

class LastLoggedAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:last-logged-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Table of the last logged admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Getting the last logged admin...');
        $admins = Admin::orderBy('last_login_ip', 'desc');
        $this->table(['ID', 'Username', 'Email', 'Last Logged At', 'Created At', 'Role'], $admins->get(['id', 'username', 'email', 'last_login_ip', 'created_at', 'role_id'])->toArray());
    }
}
