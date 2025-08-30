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


namespace App\Listeners;

use App\Models\ActionLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;

class NewLoginAccount
{
    public function handle(Login|Failed $event): void
    {
        $action = $event instanceof Login ? ActionLog::NEW_LOGIN : ActionLog::FAILED_LOGIN;
        if (\Session::has('autologin')) {
            return;
        }
        if (($event->guard == 'web' || $event->guard == 'admin') && $event->user != null) {
            if ($event->guard == 'web') {
                if ($event instanceof Login) {
                    $event->user->last_ip = request()->ip();
                }
                ActionLog::log($action, get_class($event->user), $event->user->getKey(), null, $event->user->getKey(), ['ip' => request()->ip()]);
            } else {
                if ($event instanceof Login) {
                    $event->user->last_login_ip = request()->ip();
                }
                ActionLog::log($action, get_class($event->user), $event->user->getKey(), $event->user->getKey(), null, ['ip' => request()->ip()]);
            }
            if ($event instanceof Login) {
                $event->user->last_login = now();
                $event->user->save();
            }
        }
    }
}
