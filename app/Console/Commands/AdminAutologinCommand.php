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

namespace App\Console\Commands;

use App\Models\Admin\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AdminAutologinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:admin-autologin {--email=} {--expire=60} {--unique}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create auto login link to administrator';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        if ($email != null) {
            /** @var Admin $admin */
            $admin = Admin::first()->where('email', $email)->first();
            if (! $admin) {
                $this->error('This email does not exist');

                return;
            }
        } else {
            $admin = Admin::first();
            if (! $admin) {
                $this->error('No administrator found');

                return;
            }
        }
        $key = Str::uuid();
        $admin->attachMetadata('autologin_key', $key);
        $admin->attachMetadata('autologin_expires_at', now()->addMinutes((int) $this->option('expire')));
        if ($this->option('unique')) {
            $admin->attachMetadata('autologin_unique', true);
        }
        $this->info('Autologin link: '.URL::signedRoute('admin.autologin', ['id' => $admin->id, 'token' => $key]));
    }
}
