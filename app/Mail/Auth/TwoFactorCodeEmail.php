<?php

namespace App\Mail\Auth;

use App\Models\Admin\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TwoFactorCodeEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly string $guard,
        private readonly ?string $ip = null,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $route = $this->guard === 'admin' ? 'admin.auth.2fa' : 'auth.2fa';
        $mail = EmailTemplate::getMailMessage('two_factor_code', route($route), [
            'code' => $this->code,
            'ip' => $this->ip,
            'expires_in' => 5,
            'customer' => $notifiable,
        ], $notifiable);
        if ($this->guard === 'admin') {
            $mail->metadata('disable_save', true);
        }

        return $mail;
    }
}
