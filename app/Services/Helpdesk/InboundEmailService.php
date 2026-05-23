<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Services\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportMessage;
use App\Models\Helpdesk\SupportTicket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * v2.16 — Inbound email → ticket pipeline.
 *
 * Accepts a normalised payload from the webhook controller and either:
 *   a) appends the body as a new SupportMessage if the email is a
 *      reply (In-Reply-To matches an existing inbound_message_id, or
 *      the subject contains a "#ticket-uuid" tag we drop into the
 *      outbound notifications), or
 *   b) creates a brand-new SupportTicket otherwise. Customer matching
 *      is best-effort by `from` address; mismatched emails are stored
 *      as guest tickets so support can still respond.
 *
 * The service is provider-agnostic — the controllers for Postmark,
 * Mailgun and the generic JSON shape normalise their payload to a
 * single InboundEmailPayload array shape before calling process().
 */
class InboundEmailService
{
    /**
     * @param  array{
     *     from_email: string,
     *     from_name?: ?string,
     *     subject: string,
     *     body_plain: ?string,
     *     body_html: ?string,
     *     message_id: ?string,
     *     in_reply_to: ?string,
     *     references: array<int,string>,
     * }  $payload
     */
    public function process(array $payload): SupportTicket
    {
        // De-duplicate: if we've already imported this Message-ID, return
        // the existing ticket without doing anything else.
        if (! empty($payload['message_id'])) {
            $existing = SupportMessage::where('inbound_message_id', $payload['message_id'])->first();
            if ($existing !== null) {
                return $existing->ticket;
            }
        }

        $ticket = $this->locateExistingTicket($payload);

        $bodyText = $payload['body_plain']
            ?? strip_tags((string) ($payload['body_html'] ?? ''));
        $bodyText = trim($bodyText);

        if ($ticket === null) {
            // Brand new ticket
            $customer = $this->matchCustomer($payload['from_email']);
            $department = SupportDepartment::query()->orderBy('id')->first();

            $ticket = SupportTicket::create([
                'department_id' => $department?->id,
                'customer_id' => $customer?->id,
                'guest_email' => $customer ? null : $payload['from_email'],
                'guest_name' => $customer ? null : ($payload['from_name'] ?? null),
                'status' => SupportTicket::STATUS_OPEN,
                'priority' => 'medium',
                'subject' => $this->normaliseSubject($payload['subject']),
                'inbound_message_id' => $payload['message_id'] ?? null,
            ]);
        } else {
            // Existing ticket — re-open if it was closed, refresh inbound_message_id
            if ($ticket->status === SupportTicket::STATUS_CLOSED) {
                $ticket->status = SupportTicket::STATUS_OPEN;
                $ticket->closed_at = null;
            }
            if (! empty($payload['message_id'])) {
                $ticket->inbound_message_id = $payload['message_id'];
            }
            $ticket->save();
        }

        SupportMessage::create([
            'ticket_id' => $ticket->id,
            'customer_id' => $ticket->customer_id,
            'message' => $bodyText !== '' ? $bodyText : '(empty email body)',
            'inbound_message_id' => $payload['message_id'] ?? null,
        ]);

        Log::info('[v2.16/inbound] Email ingested', [
            'ticket_id' => $ticket->id,
            'from' => $payload['from_email'],
            'message_id' => $payload['message_id'] ?? null,
        ]);

        return $ticket->fresh();
    }

    /**
     * Resolve a ticket from inbound headers — In-Reply-To match first,
     * References fallback, finally a subject tag of the form
     * "#TKT-{uuid}". Returns null when nothing matches.
     */
    private function locateExistingTicket(array $payload): ?SupportTicket
    {
        $candidates = [];
        if (! empty($payload['in_reply_to'])) {
            $candidates[] = $payload['in_reply_to'];
        }
        foreach ($payload['references'] ?? [] as $ref) {
            $candidates[] = $ref;
        }
        $candidates = array_filter(array_unique($candidates));

        if (! empty($candidates)) {
            $message = SupportMessage::whereIn('inbound_message_id', $candidates)->latest('id')->first();
            if ($message !== null) {
                return $message->ticket;
            }
            $byTicket = SupportTicket::whereIn('inbound_message_id', $candidates)->latest('id')->first();
            if ($byTicket !== null) {
                return $byTicket;
            }
        }

        // Subject tag like "Re: [Support TKT-abc123] Hello"
        if (! empty($payload['subject']) && preg_match('/\[(?:support\s+)?TKT-([A-Za-z0-9\-]+)\]/i', $payload['subject'], $m)) {
            return SupportTicket::where('uuid', $m[1])->first();
        }

        return null;
    }

    private function matchCustomer(string $email): ?Customer
    {
        return Customer::where('email', strtolower(trim($email)))->first();
    }

    private function normaliseSubject(string $subject): string
    {
        // Strip "Re: ", "Fwd: " and the [TKT-…] tag — they pollute the
        // ticket subject when copy-pasted from the inbound subject.
        $subject = preg_replace('/^\s*(re|fw|fwd|tr)\s*:\s*/i', '', $subject) ?? $subject;
        $subject = preg_replace('/\s*\[(?:support\s+)?TKT-[A-Za-z0-9\-]+\]\s*/i', ' ', $subject) ?? $subject;

        return trim($subject) ?: '(no subject)';
    }
}
