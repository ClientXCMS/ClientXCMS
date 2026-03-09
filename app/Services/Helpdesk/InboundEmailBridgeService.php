<?php

namespace App\Services\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportDepartment;
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
            return $this->handleNewTicket($request);
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

    private function handleNewTicket(Request $request): array
    {
        $sender = $this->extractEmail((string) ($request->input('sender') ?? $request->input('from') ?? ''));
        if (! $sender) {
            return ['status' => 422, 'payload' => ['error' => 'invalid_sender']];
        }

        $customer = Customer::whereRaw('LOWER(email) = ?', [strtolower($sender)])->first();
        if (! $customer) {
            return ['status' => 422, 'payload' => ['error' => 'sender_not_client']];
        }

        $department = SupportDepartment::query()->first();
        if (! $department) {
            return ['status' => 422, 'payload' => ['error' => 'department_not_configured']];
        }

        $subject = trim((string) ($request->input('subject') ?? $request->input('stripped-subject') ?? ''));
        if ($subject === '') {
            $subject = 'Inbound email';
        }

        $content = trim((string) ($request->input('stripped-text') ?? $request->input('text') ?? $request->input('body-plain') ?? ''));
        if ($content === '') {
            return ['status' => 422, 'payload' => ['error' => 'empty_content']];
        }

        $ticket = new SupportTicket;
        $ticket->fill([
            'department_id' => $department->id,
            'priority' => 'medium',
            'subject' => $subject,
        ]);
        $ticket->customer_id = $customer->id;
        $ticket->status = SupportTicket::STATUS_OPEN;
        $ticket->save();

        $ticket->addMessage($content, $customer->id, null);
        foreach ($request->file('attachments', []) as $attachment) {
            $ticket->addAttachment($attachment, $customer->id, null);
        }

        return ['status' => 200, 'payload' => ['success' => true, 'ticket_uuid' => $ticket->uuid]];
    }

    private function extractEmail(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/<([^>]+)>/', $raw, $m)) {
            $raw = trim($m[1]);
        }

        return filter_var($raw, FILTER_VALIDATE_EMAIL) ? strtolower($raw) : null;
    }
}
