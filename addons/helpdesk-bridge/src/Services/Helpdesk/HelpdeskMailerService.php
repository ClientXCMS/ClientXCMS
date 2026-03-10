<?php

namespace App\Addons\HelpdeskBridge\Services\Helpdesk;

use Illuminate\Notifications\Messages\MailMessage;

class HelpdeskMailerService
{
    public static function apply(MailMessage $mail): MailMessage
    {
        $mailFrom = setting('mail_from');
        $mailName = setting('mail_name');

        if (is_string($mailFrom) && $mailFrom !== '' && is_string($mailName) && $mailName !== '') {
            $mail->from($mailFrom, $mailName);
        }

        return $mail;
    }
}
