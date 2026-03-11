<?php

namespace App\Addons\HelpdeskBridge\Services\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportTicket;
use Illuminate\Http\Request;

class InboundEmailBridgeService
{
    public function handle(Request $request): array
    {
        if (! $this->isAuthorized($request)) {
            return $this->error('unauthorized', 401);
        }

        $recipient = $this->getInput($request, ['recipient', 'to']);
        $sender = strtolower(trim((string) $this->getInput($request, ['sender', 'from'])));
        $subject = trim((string) $this->getInput($request, ['subject'])) ?: __('helpdesk-bridge::helpdesk_bridge.inbound.default_subject');
        $message = trim((string) $this->getInput($request, ['text', 'stripped-text', 'body-plain']));

        if ($recipient === null || $recipient === '') {
            return $this->error('invalid_recipient', 422);
        }

        if ($sender === '' || ! filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            return $this->error('invalid_sender', 422);
        }

        if ($message === '') {
            return $this->error('empty_message', 422);
        }

        $parsed = InboundReplyService::parseRecipient($recipient);
        if ($parsed !== null) {
            return $this->appendToExistingTicket($parsed, $sender, $message, $request);
        }

        if (! setting('helpdesk_bridge_create_ticket_from_inbound', true)) {
            return $this->error('invalid_recipient', 422);
        }

        return $this->createTicketFromInbound($sender, $subject, $message, $request);
    }

    private function appendToExistingTicket(array $parsed, string $sender, string $message, Request $request): array
    {
        $ticket = SupportTicket::where('uuid', $parsed['uuid'])->first();
        if (! $ticket) {
            return $this->error('invalid_recipient', 422);
        }

        if (! InboundReplyService::validateSignature($ticket->uuid, $parsed['signature'])) {
            return $this->error('invalid_signature', 422);
        }

        $customer = $ticket->customer;
        if (! $customer || strtolower($customer->email) !== $sender) {
            return $this->error('invalid_sender', 422);
        }

        $ticket->addMessage($message, $customer->id);
        $this->storeAttachments($ticket, $request, $customer->id);

        return [
            'success' => true,
            'status' => 200,
            'type' => 'reply',
            'ticket_uuid' => $ticket->uuid,
        ];
    }

    private function createTicketFromInbound(string $sender, string $subject, string $message, Request $request): array
    {
        $customer = Customer::whereRaw('LOWER(email) = ?', [$sender])->first();
        if (! $customer) {
            return $this->error('sender_not_client', 422);
        }

        $department = SupportDepartment::query()->orderBy('id')->first();
        if (! $department) {
            return $this->error('no_department', 422);
        }

        $ticket = SupportTicket::create([
            'department_id' => $department->id,
            'customer_id' => $customer->id,
            'status' => SupportTicket::STATUS_OPEN,
            'priority' => 'medium',
            'subject' => $subject,
        ]);

        $ticket->addMessage($message, $customer->id);
        $this->storeAttachments($ticket, $request, $customer->id);

        return [
            'success' => true,
            'status' => 200,
            'type' => 'created',
            'ticket_uuid' => $ticket->uuid,
        ];
    }

    private function storeAttachments(SupportTicket $ticket, Request $request, int $customerId): void
    {
        $attachments = $request->file('attachments', []);

        if (! is_array($attachments)) {
            return;
        }

        foreach ($attachments as $attachment) {
            if (! $attachment) {
                continue;
            }
            $ticket->addAttachment($attachment, $customerId);
        }
    }

    private function getInput(Request $request, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = $request->input($key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function isAuthorized(Request $request): bool
    {
        $expected = (string) setting('helpdesk_inbound_webhook_token', '');
        if ($expected === '') {
            return false;
        }

        $provided = $request->header('X-Helpdesk-Webhook-Token', $request->input('token', ''));

        return is_string($provided) && hash_equals($expected, $provided);
    }

    private function error(string $error, int $status): array
    {
        return [
            'success' => false,
            'status' => $status,
            'error' => $error,
        ];
    }
}
