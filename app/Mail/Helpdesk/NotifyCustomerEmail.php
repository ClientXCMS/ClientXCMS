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

namespace App\Mail\Helpdesk;

use App\Models\Admin\EmailTemplate;
use App\Models\Helpdesk\SupportTicket;
use App\Services\Helpdesk\HelpdeskMailerService;
use App\Services\Helpdesk\InboundReplyService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class NotifyCustomerEmail extends Notification
{
    use Queueable, SerializesModels;

    private string $message;

    private SupportTicket $ticket;

    public function __construct(SupportTicket $ticket, string $message)
    {
        $this->ticket = $ticket;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $ticketUrl = route('front.support.show', $this->ticket->id);
        $replyAddress = InboundReplyService::replyAddress($this->ticket);

        $mail = EmailTemplate::getMailMessage('support_customer_ticket_reply', $ticketUrl, [
            'ticket' => $this->ticket,
            'message' => (string) $this->message,
            'reply_address' => $replyAddress,
        ], $notifiable);

        if (filter_var($replyAddress, FILTER_VALIDATE_EMAIL)) {
            $mail->replyTo($replyAddress);
            $mail->line('Répondez directement à cet email pour ajouter votre message au ticket.');
            $mail->line('Adresse de réponse : '.$replyAddress);
        }

        if (! empty($this->message)) {
            $mail->line('Dernière réponse : '.(string) $this->message);
        }

        return HelpdeskMailerService::apply($mail);
    }
}
