<?php

namespace App\Services\Helpdesk;

use App\Models\Helpdesk\SupportTicket;
use Illuminate\Http\Request;

class InboundEmailBridgeService
{
    public function handle(Request $request): array
    {
        $token = (string) $request->header('X-Helpdesk-Webhook-Token', $request->input('token', ''));
        if (! hash_equals((string) setting('helpdesk_inbound_webhook_token', ''), $token)) {
            return ['status' => 401, 'payload' => ['error' => 'unauthorized']];
        }

        $recipient = (string) ($request->input('recipient') ?? $request->input('to') ?? '');
        $parsed = InboundReplyService::extractFromRecipient($recipient);
        if (! $parsed) {
            return ['status' => 422, 'payload' => ['error' => 'invalid_recipient']];
        }

        $ticket = SupportTicket::where('uuid', $parsed['uuid'])->first();
        if (! $ticket) {
            return ['status' => 404, 'payload' => ['error' => 'ticket_not_found']];
        }

        if (InboundReplyService::signature($ticket) !== $parsed['sig']) {
            return ['status' => 422, 'payload' => ['error' => 'invalid_signature']];
        }

        $sender = strtolower((string) ($request->input('sender') ?? $request->input('from') ?? ''));
        if ($sender !== strtolower((string) $ticket->customer->email)) {
            return ['status' => 422, 'payload' => ['error' => 'invalid_sender']];
        }

        $content = trim((string) ($request->input('stripped-text') ?? $request->input('text') ?? $request->input('body-plain') ?? ''));
        if ($content === '') {
            return ['status' => 422, 'payload' => ['error' => 'empty_content']];
        }

        if ($ticket->isClosed()) {
            $ticket->reopen();
        }

        $ticket->addMessage($content, $ticket->customer_id, null);
        foreach ($request->file('attachments', []) as $attachment) {
            $ticket->addAttachment($attachment, $ticket->customer_id, null);
        }

        return ['status' => 200, 'payload' => ['success' => true]];
    }
}
