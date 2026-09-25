<?php

namespace Tests\Fixtures\Mail;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A notification built without EmailTemplate, the way an extension or a
 * third-party package sends one.
 */
class PlainNotification extends Notification
{
    public function __construct(private string $subject = 'Notice from an extension') {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line('Something happened on your account.');
    }
}
