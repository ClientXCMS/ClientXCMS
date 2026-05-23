<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * v2.16 — Route-level scope-aware permission middleware.
 *
 * Usage in routes:
 *   ->middleware('perm.scoped:admin.manage_tickets,department,department')
 *
 * Where the parameters are:
 *   1. permission name (e.g. 'admin.manage_tickets')
 *   2. scope type     (e.g. 'department')
 *   3. route parameter NAME whose value supplies the scope id (the
 *      middleware reads $request->route($name)).
 *
 * The middleware passes when the admin's role has either a global grant
 * for the permission OR a row scoped to (scope_type, scope_id) matching
 * the requested resource.
 */
class PermissionScoped
{
    public function handle(Request $request, Closure $next, string $permission, string $scopeType, string $scopeRouteParam): mixed
    {
        $admin = $request->user('admin');
        if ($admin === null) {
            abort(401);
        }

        $resource = $request->route($scopeRouteParam);
        $scopeId = is_object($resource) && property_exists($resource, 'id')
            ? (int) $resource->id
            : ($resource !== null ? (int) $resource : null);

        if ($scopeId === null) {
            abort(400, 'Missing scope id');
        }

        if (! $admin->role || ! $admin->role->hasScopedPermission($permission, $scopeType, $scopeId)) {
            abort(403);
        }

        return $next($request);
    }
}
