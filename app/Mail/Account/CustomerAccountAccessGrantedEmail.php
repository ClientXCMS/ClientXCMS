<?php

namespace App\Mail\Account;

use App\Models\Account\CustomerAccountAccess;
use App\Models\Admin\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class CustomerAccountAccessGrantedEmail extends Notification
{
    use Queueable, SerializesModels;

    public function __construct(private CustomerAccountAccess $access) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('front.client.index');

        return EmailTemplate::getMailMessage('customer_account_access_granted', $url, [
            'access' => $this->access,
            'owner' => $this->access->owner,
            'url' => $url,
        ], $notifiable);
    }
}
