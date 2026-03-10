<?php

namespace App\Services\Helpdesk;

use Illuminate\Notifications\Messages\MailMessage;

class HelpdeskMailerService
{
    public const MAILER = 'helpdesk_smtp';

    public static function apply(MailMessage $mail): MailMessage
    {
        if (! setting('helpdesk_smtp_enable', false)) {
            return $mail;
        }

        config([
            'mail.mailers.'.self::MAILER => [
                'transport' => 'smtp',
                'host' => setting('helpdesk_mail_smtp_host', env('MAIL_HOST')),
                'port' => (int) setting('helpdesk_mail_smtp_port', env('MAIL_PORT', 587)),
                'encryption' => setting('helpdesk_mail_smtp_encryption', env('MAIL_ENCRYPTION')) ?: null,
                'username' => setting('helpdesk_mail_smtp_username', env('MAIL_USERNAME')),
                'password' => setting('helpdesk_mail_smtp_password', env('MAIL_PASSWORD')),
                'timeout' => env('MAIL_TIMEOUT', 10),
            ],
        ]);

        $fromAddress = setting('helpdesk_mail_fromaddress', setting('mail_fromaddress', env('MAIL_FROM_ADDRESS')));
        $fromName = setting('helpdesk_mail_fromname', setting('mail_fromname', env('MAIL_FROM_NAME')));

        return $mail->mailer(self::MAILER)->from($fromAddress, $fromName);
    }
}
