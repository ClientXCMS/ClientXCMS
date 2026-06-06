<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Notifications\Account;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * v2.16 — Pre-purge reminder for inactive accounts. Sent at D-30,
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
