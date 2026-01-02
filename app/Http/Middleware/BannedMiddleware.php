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

class BannedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');
        if ($request->user('admin')) {
            return $next($request);
        }
        if ($user) {
            if ($user->isBanned()) {
                auth()->logout();

                return redirect()->route('login')->with('error', __('client.alerts.account_blocked', ['reason' => $user->getMetadata('banned_reason')]));
            }
            if ($user->isSuspended()) {
                if (! in_array($request->route()->getName(), $this->authorizedRoutes())) {
                    return redirect()->route('front.support.index')->with('warning', __('client.alerts.account_suspended', ['reason' => $user->getMetadata('suspended_reason')]));
                }
                session()->flash('warning', __('client.alerts.account_suspended', ['reason' => $user->getMetadata('suspended_reason')]));
            }
        }

        return $next($request);
    }

    public function authorizedRoutes()
    {
        return [
            'front.profile.index',
            'front.client.index',
            'front.support.index',
            'front.support.show',
            'front.support.create',
            'front.support.store',
            'front.support',
            'front.support.download',
            'front.tickets.index',
            'front.support.reply',
            'front.support.close',
            'front.support.reopen',
        ];
    }
}
