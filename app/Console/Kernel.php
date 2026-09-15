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

namespace App\Console;

use App\Models\Admin\Setting;
use App\Services\Core\QueueHealthService;
use App\Services\Core\ScheduledTasksHealthService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        if (! is_installed()) {
            return;
        }
        $schedule->call(function () {
            Setting::updateSettings([
                ScheduledTasksHealthService::HEARTBEAT_SETTING => now(),
            ], null, false);
        })->name('clientxcms:scheduler-heartbeat')->everyMinute();

        $schedule->call(function () {
            app(QueueHealthService::class)->dispatchHeartbeat();
        })->name('clientxcms:queue-heartbeat')->everyMinute();

        $schedule->call(function () {
            app(ScheduledTasksHealthService::class)->pruneHistory();
        })->name('clientxcms:scheduler-history-prune')->dailyAt('00:30');

        $schedule->command('invoices:delivery')
            ->name('invoices:delivery')->sentryMonitor()
            ->sendOutputTo(storage_path('logs/invoices-delivery.log'))
            ->everyMinute();
        $schedule->command('services:expire')
            ->everyMinute()
            ->name('services:expire')
            ->sendOutputTo(storage_path('logs/services-expire.log'));
        $schedule->command('services:renewals')
            ->everyThreeHours()->sentryMonitor()
            ->name('services:renewals')
            ->sendOutputTo(storage_path('logs/services-renewals.log'));
        $schedule->command('clientxcms:helpdesk-close')
            ->daily()->at('12:00')->sentryMonitor()
            ->name('clientxcms:helpdesk-close')
            ->sendOutputTo(storage_path('logs/helpdesk-close.log'));
        $schedule->command('services:notify-expiration')
            ->daily()->at('09:00')->sentryMonitor()
            ->name('services:notify-expiration')
            ->sendOutputTo(storage_path('logs/services-notify-expiration.log'));
        $schedule->command('clientxcms:invoice-delete')
            ->daily()->at('00:00')->sentryMonitor()
            ->name('clientxcms:invoice-delete')
            ->sendOutputTo(storage_path('logs/invoice-delete.log'))->sentryMonitor();
        $schedule->command('clientxcms:purge-metadata')
            ->weekly()->mondays()
            ->name('clientxcms:purge-metadata')
            ->sendOutputTo(storage_path('logs/purge-metadata.log'))->sentryMonitor();
        $schedule->command('clientxcms:purge-basket')
            ->weekly()->thursdays()
            ->name('clientxcms:purge-basket')
            ->sendOutputTo(storage_path('logs/purge-basket.log'))->sentryMonitor();
        $schedule->command('clientxcms:telemetry')
            ->daily()->at('00:00')
            ->name('clientxcms:telemetry')
            ->sendOutputTo(storage_path('logs/telemetry.log'))->sentryMonitor();
        $schedule->command('einvoicing:process')
            ->hourly()->withoutOverlapping()->name('einvoicing:process');
        $schedule->command('einvoicing:reconcile')
            ->hourly()->withoutOverlapping()->name('einvoicing:reconcile');
        $schedule->command('accounting:reconcile')
            ->hourly()->withoutOverlapping()->name('accounting:reconcile');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
