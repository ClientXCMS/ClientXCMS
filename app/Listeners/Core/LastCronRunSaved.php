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
namespace App\Listeners\Core;

use App\Models\Admin\Setting;
use Illuminate\Console\Events\ScheduledTaskStarting;

class LastCronRunSaved
{
    public function handle(ScheduledTaskStarting $task): void
    {
        Setting::updateSettings(['app_cron_last_run' => now()], null, false);
    }
}
