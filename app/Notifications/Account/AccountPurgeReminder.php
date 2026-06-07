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

namespace App\Notifications\Account;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pre-purge reminder for inactive accounts. Sent at D-30,
 * D-7 and D-1 by {@see \App\Console\Commands\Purge\PurgeInactiveAccountsCommand}.
 *
 * The mail uses the standard Notification channel so operators that
 * have customised the email layout (notifications::custom view) keep
 * their branding. The CTA points to /login so the customer can prove
 * activity and reset the timer.
 */
class AccountPurgeReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $daysLeft) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'ClientXCMS');
        $loginUrl = url('/login');

        $line1 = __('v216::gdpr.purge_reminder.line1', ['days' => $this->daysLeft, 'app' => $appName]);
        $line2 = __('v216::gdpr.purge_reminder.line2');

        return (new MailMessage)
            ->subject(__('v216::gdpr.purge_reminder.subject', ['days' => $this->daysLeft]))
            ->greeting(__('v216::gdpr.purge_reminder.greeting'))
            ->line($line1)
            ->line($line2)
            ->action(__('v216::gdpr.purge_reminder.cta'), $loginUrl);
    }
}
