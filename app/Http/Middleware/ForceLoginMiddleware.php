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
use Illuminate\Support\Facades\Auth;

class ForceLoginMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (setting('force_login_client') == 'true') {
            if (! $request->is($this->allowedRoutes())) {
                if (! Auth::check()) {
                    return redirect('/login');
                }
            }
        }

        return $next($request);
    }

    private function allowedRoutes()
    {
        return [
            'login',
            'register',
            'reset-password',
            'admin*',
            'verify-email',
            'forgot-password',
            'password/reset*',
            'password/email',
            'licensing/return',
        ];
    }
}
