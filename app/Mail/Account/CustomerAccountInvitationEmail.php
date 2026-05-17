<?php

namespace App\Mail\Account;

use App\Models\Account\CustomerAccountInvitation;
use App\Models\Admin\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class CustomerAccountInvitationEmail extends Notification
{
    use Queueable, SerializesModels;

    public function __construct(private CustomerAccountInvitation $invitation) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('front.subusers.accept', $this->invitation->token);
        $registerUrl = route('register', [
            'redirect' => $url,
            'email' => $this->invitation->email,
        ]);

        return EmailTemplate::getMailMessage('customer_account_invitation', $url, [
            'invitation' => $this->invitation,
            'owner' => $this->invitation->owner,
            'url' => $url,
            'register_url' => $registerUrl,
        ], $notifiable);
    }
}
