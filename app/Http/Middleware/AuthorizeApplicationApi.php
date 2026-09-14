<?php

namespace App\Http\Middleware;

use App\Models\Admin\Admin;
use App\Models\Admin\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeApplicationApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user();

        if (! $admin instanceof Admin || ! $admin->isActive() || ! $admin->role) {
            $this->deny($request, 'not_an_active_admin');
        }

        // A session principal carries a TransientToken that answers true to every ability, and it exists before the second factor is validated: this API is token only.
        if ($admin->currentAccessToken() instanceof TransientToken) {
            $this->deny($request, 'session_principal');
        }

        $name = $request->route()?->getName();
        $parts = explode('.', (string) $name);
        $resource = $parts[2] ?? null;
        $action = $parts[3] ?? null;

        $permissions = match ($resource) {
            'health' => [Permission::ALLOWED],
            'license' => [Permission::MANAGE_LICENSE],
            'customers' => in_array($action, ['index', 'show'], true)
                ? [Permission::SHOW_CUSTOMERS, Permission::MANAGE_CUSTOMERS]
                : [Permission::MANAGE_CUSTOMERS],
            'invoices' => match ($action) {
                'index', 'show', 'pdf', 'download' => [Permission::SHOW_INVOICES, Permission::MANAGE_INVOICES],
                'store' => [Permission::CREATE_INVOICES, Permission::MANAGE_INVOICES],
                'export' => [Permission::EXPORT_INVOICES, Permission::MANAGE_INVOICES],
                default => [Permission::MANAGE_INVOICES],
            },
            'services' => in_array($action, ['index', 'show'], true)
                ? [Permission::SHOW_SERVICES, Permission::MANAGE_SERVICES]
                : [Permission::MANAGE_SERVICES],
            'tickets' => match ($action) {
                'index', 'show' => [Permission::MANAGE_TICKETS],
                'close', 'reopen' => [Permission::CLOSE_TICKETS, Permission::MANAGE_TICKETS],
                default => [Permission::MANAGE_TICKETS],
            },
            'departments' => [Permission::MANAGE_DEPARTMENTS],
            'products', 'pricings' => [Permission::MANAGE_PRODUCTS],
            'groups' => [Permission::MANAGE_GROUPS],
            'coupons' => [Permission::MANAGE_COUPONS],
            'servers' => [Permission::MANAGE_SERVERS],
            'logs' => [Permission::SHOW_LOGS],
            'subdomains' => [Permission::MANAGE_SUBDOMAINS_HOSTS],
            'cancellation_reasons' => [Permission::MANAGE_SERVICES],
            default => [],
        };

        if ($permissions === [] || ! $admin->role->is_admin && ! $admin->role->hasAnyPermission($permissions)) {
            $this->deny($request, 'missing_permission');
        }

        return $next($request);
    }

    private function deny(Request $request, string $reason): never
    {
        $principal = $request->user();

        Log::warning('Application API request refused', [
            'reason' => $reason,
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'principal' => $principal ? $principal::class : null,
            'principal_id' => $principal?->getAuthIdentifier(),
        ]);

        abort(403);
    }
}
