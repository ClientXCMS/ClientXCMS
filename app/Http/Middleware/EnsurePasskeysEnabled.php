<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasskeysEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(setting('passkeys_enabled', false), 404);

        if (app()->environment('production') && ! $request->isSecure()) {
            abort(400, 'Passkeys require HTTPS.');
        }

        if (app()->environment('production')) {
            foreach (config('passkeys.allowed_origins', []) as $origin) {
                abort_unless(parse_url($origin, PHP_URL_SCHEME) === 'https', 500, 'Passkey origins must use HTTPS.');
            }
        }

        return $next($request);
    }
}
