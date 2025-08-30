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

use App\Models\Provisioning\Service;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasServicePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $service = Service::findOrFail($request->route()->parameter('service'));
        // Si il est pas en mode admin
        if (auth('admin')->guest()) {
            // Si il est pas connecté
            abort_if(auth('web')->user() == null, 404);

            // Si il a pas la permission sur le service
            abort_if(! auth()->user()->hasServicePermission($service, $permission), 404);
        } else {
            // TODO : rajouter les permissions sur l'administration
        }
        $request->route()->setParameter('service', $service);

        return $next($request);
    }
}
