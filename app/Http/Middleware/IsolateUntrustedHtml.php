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
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves a mail body without letting it run anything.
 *
 * A sent mail is archived as HTML and handed back to a browser, both in the
 * admin and in the customer area. The body is written by staff, so it may carry
 * a script the mail client would never have run but a browser will.
 *
 * This is the per-route policy SetSecurityHeaders points to: a global CSP would
 * break the application's own pages, which ship third-party assets.
 *
 * Styles stay inline because mail is written that way, and images stay open
 * because a mail legitimately points at remote ones. Neither executes.
 */
class IsolateUntrustedHtml
{
    private const POLICY = [
        "default-src 'none'",
        'img-src * data:',
        "style-src 'unsafe-inline'",
        'font-src data:',
        "base-uri 'none'",
        "form-action 'none'",
        "frame-ancestors 'none'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', implode('; ', self::POLICY));
        $response->headers->set('X-Frame-Options', 'DENY');

        return $response;
    }
}
