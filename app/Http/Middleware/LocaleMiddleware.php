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

use App\Services\Core\LocaleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query('locale') && in_array($request->query('locale'), array_keys(LocaleService::getLocales()))) {
            LocaleService::setLocale($request->query('locale'));

            return $next($request);
        }
        $fromBrowser = substr($request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);
        if ($fromBrowser && in_array($fromBrowser, array_keys(LocaleService::getLocales()))) {
            LocaleService::setLocale($fromBrowser);
        }
        $fromCookie = $request->cookie('locale');
        if ($fromCookie && in_array($fromCookie, array_keys(LocaleService::getLocales()))) {
            LocaleService::setLocale($fromCookie);
        }
        $currentLocale = LocaleService::fetchCurrentLocale();
        if ($currentLocale !== app()->getLocale()) {
            LocaleService::setLocale($currentLocale);
        }

        return $next($request);
    }
}
