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

namespace App\Mail\Service;

use App\Models\Admin\EmailTemplate;
use App\Models\Provisioning\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class ServiceSuspendedEmail extends Notification
{
    use Queueable, SerializesModels;

    private Service $service;

    private string $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(Service $service, string $reason = 'No reason provided')
    {
        $this->service = $service;
        $this->reason = $reason;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $invoiceUrl = route('front.services.show', $this->service->id);
        $context = [
            'service' => $this->service,
            'reason' => $this->reason,
        ];

        return EmailTemplate::getMailMessage('service_suspended', $invoiceUrl, $context, $notifiable);
    }
}
