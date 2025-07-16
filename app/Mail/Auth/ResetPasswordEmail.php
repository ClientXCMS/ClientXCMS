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
namespace App\Mail\Auth;

use App\Models\Admin\Admin;
use App\Models\Admin\EmailTemplate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;

class ResetPasswordEmail extends ResetPassword implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Queueable;

    public function toMail($notifiable)
    {
        if ($notifiable instanceof Admin) {
            $resetUrl = url(route('admin.password.reset', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $mail = EmailTemplate::getMailMessage('reset', $resetUrl, ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'), 'customer' => $notifiable], $notifiable);
            $mail->metadata('disable_save', true);

            return $mail;
        }
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return EmailTemplate::getMailMessage('reset', $resetUrl, ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'), 'customer' => $notifiable], $notifiable);
    }
}
