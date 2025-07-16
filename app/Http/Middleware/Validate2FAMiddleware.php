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
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class Validate2FAMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_installed()) {
            return $next($request);
        }
        if (auth('web')->user() && ! Session::has('autologin')) {
            if (auth('web')->user()->twoFactorEnabled() && ! auth('web')->user()->twoFactorVerified() && $request->is('client/*')) {
                if ($request->route()->uri() !== '2fa') {
                    return redirect()->route('auth.2fa');
                }
            }
        }
        if (auth('admin')->user() && ! Session::has('autologin')) {
            if (auth('admin')->user()->twoFactorEnabled() && ! auth('admin')->user()->twoFactorVerified() && $request->is(admin_prefix('/*'))) {
                if ($request->route()->uri() !== admin_prefix('2fa')) {
                    return redirect()->route('admin.auth.2fa');
                }
            }
        }

        return $next($request);
    }
}
