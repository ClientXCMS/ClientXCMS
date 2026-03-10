<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Helpdesk\SupportTicket;
use App\Services\Helpdesk\InboundReplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpdeskInboundEmailController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) $request->header('X-Helpdesk-Webhook-Token', $request->input('token', ''));
        if (! hash_equals((string) setting('helpdesk_inbound_webhook_token', ''), $token)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $recipient = (string) ($request->input('recipient') ?? $request->input('to') ?? '');
        $parsed = InboundReplyService::extractFromRecipient($recipient);
        if (! $parsed) {
            return response()->json(['error' => 'invalid_recipient'], 422);
        }

        $ticket = SupportTicket::where('uuid', $parsed['uuid'])->first();
        if (! $ticket) {
            return response()->json(['error' => 'ticket_not_found'], 404);
        }

        if (InboundReplyService::signature($ticket) !== $parsed['sig']) {
            return response()->json(['error' => 'invalid_signature'], 422);
        }

        $sender = strtolower((string) ($request->input('sender') ?? $request->input('from') ?? ''));
        if ($sender !== strtolower((string) $ticket->customer->email)) {
            return response()->json(['error' => 'invalid_sender'], 422);
        }

        $content = trim((string) ($request->input('stripped-text') ?? $request->input('text') ?? $request->input('body-plain') ?? ''));
        if ($content === '') {
            return response()->json(['error' => 'empty_content'], 422);
        }

        if ($ticket->isClosed()) {
            $ticket->reopen();
        }

        $ticket->addMessage($content, $ticket->customer_id, null);
        foreach ($request->file('attachments', []) as $attachment) {
            $ticket->addAttachment($attachment, $ticket->customer_id, null);
        }

        return response()->json(['success' => true]);
    }
}
