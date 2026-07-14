<?php

namespace App\Mail\Account;

use App\Models\Account\CustomerAccountInvitation;
use App\Models\Admin\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class CustomerAccountInvitationEmail extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The plain token must be passed in by the caller because the model
     * never persists it. Looking at $invitation->token would surface the
     * sha256 hash and produce an unusable URL.
     */
    public function __construct(private CustomerAccountInvitation $invitation, private string $plainToken) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('front.subusers.accept', $this->plainToken);
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
