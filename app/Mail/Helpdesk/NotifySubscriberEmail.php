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
namespace App\Mail\Helpdesk;

use App\Models\Admin\EmailTemplate;
use App\Models\Helpdesk\SupportTicket;
use DragonCode\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class NotifySubscriberEmail extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    private bool $firstMessage;

    private string $message;

    private SupportTicket $ticket;

    public function __construct(SupportTicket $ticket, string $message, bool $firstMessage = false)
    {
        $this->ticket = $ticket;
        $this->message = $message;
        $this->firstMessage = $firstMessage;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return $this->firstMessage
            ? $this->firstMessageMail($notifiable)
            : $this->replyMessageMail($notifiable);
    }

    public function firstMessageMail($notifiable): MailMessage
    {
        $ticketUrl = route('admin.support.tickets.show', $this->ticket->id);

        return EmailTemplate::getMailMessage('support_admin_ticket_created', $ticketUrl, [
            'ticket' => $this->ticket,
            'message' => $this->message,
        ], $notifiable);
    }

    public function replyMessageMail($notifiable): MailMessage
    {
        $ticketUrl = route('admin.support.tickets.show', $this->ticket->id);

        return EmailTemplate::getMailMessage('support_admin_ticket_reply', $ticketUrl, [
            'ticket' => $this->ticket,
            'message' => $this->message,
        ], $notifiable);
    }
}
