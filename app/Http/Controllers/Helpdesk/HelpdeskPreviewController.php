<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * v2.16 — Server-side preview of helpdesk messages.
 *
 * The bundled EasyMDE preview uses a JS Markdown engine that diverges
 * from the Parsedown output the recipient actually sees in the message
 * thread (e.g. heading hashes are not rendered, list bullets differ).
 *
 * This endpoint runs the same Parsedown safe-mode pipeline as
 * SupportMessage::formattedMessage() so the "Preview" button shows
 * exactly what the other party will see after submit.
 *
 * Auth: any signed-in customer or admin can preview their own content.
 * The endpoint never persists anything and the rate limiter caps abuse.
 */
class HelpdeskPreviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'nullable|string|max:20000',
        ]);

        // Same instance + settings as SupportMessage::formattedMessage()
        $parser = new \Parsedown;
        $parser->setSafeMode(true);

        $raw = (string) $request->input('content', '');
        // We deliberately drop nl2br here — the new helpdesk.css styles
        // recognise <h*>, <ul>, <hr> properly, no manual <br> injection.
        $html = $parser->parse($raw);

        return response()->json([
            'html' => $html,
        ]);
    }
}
