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


namespace App\Mail\Invoice;

use App\Abstracts\PaymentMethodSourceDTO;
use App\Models\Admin\EmailTemplate;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class SubscriptionFailedEmail extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    private Invoice $invoice;

    private Subscription $subscription;

    private PaymentMethodSourceDTO $sourceDTO;

    private bool $retry;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice, Subscription $subscription, PaymentMethodSourceDTO $sourceDTO, bool $retry)
    {
        $this->invoice = $invoice;
        $this->subscription = $subscription;
        $this->sourceDTO = $sourceDTO;
        $this->retry = $retry;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $paymentMethod = route('front.payment-methods.index');

        return EmailTemplate::getMailMessage('notify_subscription_failed', $paymentMethod, [
            'invoice' => $this->invoice,
            'subscription' => $this->subscription,
            'source' => $this->sourceDTO->title(),
            'retry' => $this->retry,
        ], $notifiable);
    }
}
