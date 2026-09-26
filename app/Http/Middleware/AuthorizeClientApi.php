<?php

namespace App\Http\Middleware;

use App\Models\Account\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeClientApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user();

        if (! $customer instanceof Customer) {
            $this->deny($request, 'not_a_customer');
        }

        // A session principal carries a TransientToken that answers true to every ability, and it exists before the second factor is validated: this API is token only.
        if ($customer->currentAccessToken() instanceof TransientToken) {
            $this->deny($request, 'session_principal');
        }

        if ($customer->isBanned()) {
            $this->deny($request, 'banned');
        }

        if ($customer->isSuspended() && ! BannedMiddleware::allowsSuspendedAccess($request->route()?->getName())) {
            $this->deny($request, 'suspended');
        }

        return $next($request);
    }

    private function deny(Request $request, string $reason): never
    {
        $principal = $request->user();

        Log::warning('Client API request refused', [
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
