<?php

namespace App\Services\Helpdesk;

use App\Models\Helpdesk\SupportTicket;

class InboundReplyService
{
    public static function signature(SupportTicket $ticket): string
    {
        return substr(hash_hmac('sha256', $ticket->uuid.'|'.strtolower($ticket->customer->email), (string) config('app.key')), 0, 24);
    }

    public static function replyAddress(SupportTicket $ticket): string
    {
        $localPart = setting('helpdesk_reply_mailbox', 'support-reply');
        $mailDomain = parse_url((string) setting('mail_domain', config('app.url')), PHP_URL_HOST) ?: parse_url((string) config('app.url'), PHP_URL_HOST);

        return sprintf('%s+%s.%s@%s', $localPart, $ticket->uuid, self::signature($ticket), $mailDomain ?: 'localhost');
    }

    public static function extractFromRecipient(string $recipient): ?array
    {
        if (! preg_match('/\+([a-zA-Z0-9-]+)\.([a-f0-9]{24})@/i', $recipient, $m)) {
            return null;
        }

        return ['uuid' => $m[1], 'sig' => strtolower($m[2])];
    }
}
