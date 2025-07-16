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
namespace App\Listeners;

use App\Models\Account\Customer;
use App\Models\Account\EmailMessage;
use Carbon\Carbon;
use Illuminate\Mail\Events\MessageSending;

class LogSentMessage
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSending $event): void
    {
        if ($event->message->getHeaders()->has('x-metadata-disable_save')) {
            return;
        }
        $params = [
            'recipient' => $event->message->getTo()[0]->getAddress(),
            'subject' => $event->message->getSubject(),
            'content' => $event->message->getHtmlBody(),
            'recipient_id' => Customer::whereEmail($event->message->getTo()[0]->getAddress())->first()->id ?? null,
            'template' => $event->data['template'] ?? null,
            'created_at' => Carbon::now(),
        ];
        EmailMessage::insert($params);
    }
}
