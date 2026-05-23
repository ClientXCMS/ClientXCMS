<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Helpdesk\InboundEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * v2.16 — Generic inbound email webhook used by Postmark, Mailgun and
 * the bare-bones JSON callers (curl from a Postfix `pipe` rule, an n8n
 * workflow, …). The path is protected by a shared secret token taken
 * from settings, so anyone with the URL can post but nobody else can.
 *
 *   POST /webhooks/helpdesk/inbound/{token}
 *
 * Body shapes accepted (auto-detected from headers / keys):
 *
 *   • Postmark JSON   — has `From`, `Subject`, `TextBody`, `MessageID`
 *   • Mailgun JSON    — has `sender`, `subject`, `body-plain`, `Message-Id`
 *   • Generic JSON    — has `from`, `subject`, `text` (or `body`)
 *   • application/x-www-form-urlencoded — same field names as Mailgun
 *
 * No HTML parsing magic — we keep the message_id headers for threading
 * but don't try to rewrite quoted replies; they show up as part of the
 * inbound message which is usually what the support staff wants anyway.
 */
class HelpdeskInboundController extends Controller
{
    public function __invoke(Request $request, string $token, InboundEmailService $service): JsonResponse
    {
        $expected = (string) setting('helpdesk_inbound_token', '');
        if ($expected === '' || ! hash_equals($expected, $token)) {
            Log::warning('[v2.16/inbound] rejected request — invalid token', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'forbidden'], 403);
        }

        try {
            $payload = $this->normalise($request);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $ticket = $service->process($payload);

        return response()->json([
            'ok' => true,
            'ticket_uuid' => $ticket->uuid,
            'ticket_id' => $ticket->id,
        ]);
    }

    /**
     * Detect provider format and produce the canonical payload shape
     * expected by InboundEmailService::process().
     */
    private function normalise(Request $request): array
    {
        // Postmark — JSON, capitalised keys
        if ($request->has(['From', 'Subject']) && $request->has(['TextBody', 'HtmlBody'])) {
            return [
                'from_email' => (string) $request->input('FromFull.Email', $request->input('From')),
                'from_name' => $request->input('FromFull.Name'),
                'subject' => (string) $request->input('Subject', '(no subject)'),
                'body_plain' => $request->input('TextBody'),
                'body_html' => $request->input('HtmlBody'),
                'message_id' => $this->cleanMessageId($request->input('MessageID')),
                'in_reply_to' => $this->cleanMessageId(
                    collect($request->input('Headers', []))
                        ->firstWhere('Name', 'In-Reply-To')['Value'] ?? null
                ),
                'references' => $this->splitReferences(
                    collect($request->input('Headers', []))
                        ->firstWhere('Name', 'References')['Value'] ?? null
                ),
            ];
        }

        // Mailgun (form-encoded or JSON)
        if ($request->has(['sender', 'subject']) && ($request->has('body-plain') || $request->has('body-html'))) {
            return [
                'from_email' => (string) $request->input('sender'),
                'from_name' => $request->input('From'),
                'subject' => (string) $request->input('subject', '(no subject)'),
                'body_plain' => $request->input('body-plain') ?? $request->input('stripped-text'),
                'body_html' => $request->input('body-html'),
                'message_id' => $this->cleanMessageId($request->input('Message-Id')),
                'in_reply_to' => $this->cleanMessageId($request->input('In-Reply-To')),
                'references' => $this->splitReferences($request->input('References')),
            ];
        }

        // Generic JSON fallback
        $from = $request->input('from');
        $subject = $request->input('subject');
        if ($from === null || $subject === null) {
            throw new \InvalidArgumentException(
                'Inbound payload missing required fields (from, subject).'
            );
        }

        return [
            'from_email' => (string) $from,
            'from_name' => $request->input('from_name'),
            'subject' => (string) $subject,
            'body_plain' => $request->input('text') ?? $request->input('body'),
            'body_html' => $request->input('html'),
            'message_id' => $this->cleanMessageId($request->input('message_id')),
            'in_reply_to' => $this->cleanMessageId($request->input('in_reply_to')),
            'references' => $this->splitReferences($request->input('references')),
        ];
    }

    private function cleanMessageId(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        // Strip the surrounding < > that some MTAs include.
        return trim($raw, '<> ');
    }

    /**
     * Splits a "References:" header value into individual <id> tokens.
     *
     * @return array<int, string>
     */
    private function splitReferences(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (! preg_match_all('/<([^>]+)>/', $raw, $matches)) {
            return [];
        }

        return $matches[1];
    }
}
