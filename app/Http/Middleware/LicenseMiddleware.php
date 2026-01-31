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

use App\Exceptions\LicenseInvalidException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_installed() || $request->is('licensing/return')) {
            return $next($request);
        }
        try {
            if (app('license')->getLicense() && app('license')->hasExpiredFile() && ! \App::runningUnitTests()) {
                \Session::flash('error', 'Your license is expired. Please renew your license.');
                $oauth_url = app('license')->getAuthorizationUrl();

                return new \Illuminate\Http\Response(view('admin.auth.license', ['oauth_url' => $oauth_url]), 401);
            }
        } catch (LicenseInvalidException $e) {
            if (auth('admin')->check() && ! \App::runningUnitTests()) {
                \Session::flash('error', $e->getMessage());
                $oauth_url = app('license')->getAuthorizationUrl();

                return new \Illuminate\Http\Response(view('admin.auth.license', ['oauth_url' => $oauth_url]), 401);
            }
        }

        return $next($request);
    }
}
