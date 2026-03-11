<?php

namespace App\Addons\HelpdeskBridge\Services\Helpdesk;

use App\Models\Helpdesk\SupportTicket;

class InboundReplyService
{
    public static function replyAddress(SupportTicket $ticket): string
    {
        $localPart = setting('helpdesk_reply_mailbox', 'support');
        $domain = setting('mail_domain', parse_url(config('app.url'), PHP_URL_HOST));

        $signature = self::signTicket($ticket->uuid);

        return sprintf('%s+%s.%s@%s', $localPart, $ticket->uuid, $signature, $domain);
    }

    public static function parseRecipient(string $recipient): ?array
    {
        $recipient = strtolower(trim($recipient));

        if (! preg_match('/^[^+]+\+([^\.@]+)\.([^@]+)@.+$/', $recipient, $matches)) {
            return null;
        }

        return [
            'uuid' => $matches[1],
            'signature' => $matches[2],
        ];
    }

    public static function signTicket(string $ticketUuid): string
    {
        $key = config('app.key', 'helpdesk-bridge');

        return substr(hash_hmac('sha256', $ticketUuid, (string) $key), 0, 24);
    }

    public static function validateSignature(string $ticketUuid, string $signature): bool
    {
        return hash_equals(self::signTicket($ticketUuid), $signature);
    }
}
