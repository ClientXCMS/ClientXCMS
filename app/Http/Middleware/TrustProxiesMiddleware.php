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
use URL;

class TrustProxiesMiddleware extends TrustProxies
{
    protected $proxies = '*';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('X-Forwarded-For', $request->header('CF-Connecting-IP'));
        Request::setTrustedProxies($this->proxies, $this->headers);
        if (! $request->secure()) {
            $this->setProtocolForRequest($request);
        }
        if (! $request->secure() && $request->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        return $next($request);
    }

    protected function setProtocolForRequest(Request $request): void
    {
        $cfVisitorHeader = $request->header('CF-Visitor');
        if ($cfVisitorHeader === null) {
            return;
        }
        $cfVisitor = json_decode($cfVisitorHeader);
        if (! isset($cfVisitor->scheme)) {
            return;
        }
        $request->headers->add([
            'X-Forwarded-Proto' => $cfVisitor->scheme,
            'X-Forwarded-Port' => $cfVisitor->scheme === 'https' ? 443 : 80,
        ]);

        if ($cfVisitor->scheme === 'https' && ! $request->secure()) {
            $request->server->set('HTTPS', 'on');
        }
    }
}
