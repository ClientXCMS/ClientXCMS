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
use Carbon\Carbon;
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
        $admins = Admin::orderBy('last_login', 'desc')->get();
        foreach ($admins as $admin) {
            if ($admin->last_login == null) {
                $this->error('No admin has logged in yet.');
                continue;
            }
            /** @var Carbon $carbon */
            $carbon = $admin->last_login;
            if ($carbon->diffInDays(Carbon::now()) > 30) {
                $this->error('No admin has logged in the last 30 days.');
                continue;
            }
            if ($carbon->diffInDays(Carbon::now()) > 7) {
                $this->warn('No Admin has logged in the last 7 days.');
                continue;
            }
            if ($carbon->diffInDays(Carbon::now()) < 3) {
                $this->info('Admin has logged in the last 3 days.');
            }
        }
    }
}
