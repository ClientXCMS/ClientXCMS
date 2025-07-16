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
 * Year: 2025
 */
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use URL;

class TrustProxiesMiddleware extends TrustProxies
{
    protected $proxies = [
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '108.162.192.0/18',
        '131.0.72.0/22',
        '141.101.64.0/18',
        '162.158.0.0/15',
        '172.64.0.0/13',
        '173.245.48.0/20',
        '188.114.96.0/20',
        '190.93.240.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

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
