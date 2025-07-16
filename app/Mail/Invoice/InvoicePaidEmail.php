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
namespace App\Mail\Invoice;

use App\Models\Admin\EmailTemplate;
use App\Models\Billing\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class InvoicePaidEmail extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    private Invoice $invoice;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $invoiceUrl = route('front.invoices.show', $this->invoice);
        $context = [
            'invoice' => $this->invoice,
        ];

        return EmailTemplate::getMailMessage('invoice_paid', $invoiceUrl, $context, $notifiable)->attachData($this->invoice->invoiceOutput(), $this->invoice->identifier().'.pdf', [
            'mime' => 'application/pdf',
        ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachment = Attachment::fromData(function () {
            return $this->invoice->pdf();
        }, $this->invoice->identifier().'.pdf', [
            'mime' => 'application/pdf',
        ]);

        return [$attachment];
    }
}
