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

use App\Models\Admin\Admin;
use Closure;
use Illuminate\Http\Request;
use Schema;
use Symfony\Component\HttpFoundation\Response;

class InstallationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_installed()) {
            $isEnvFileIsValid = app('installer')->isEnvFileIsValid();
            if ($isEnvFileIsValid->getStatusCode() != 200) {
                return $isEnvFileIsValid;
            }
            if ($request->is('licensing/return')) {
                return $next($request);
            }
            $step = $this->step();
            if ($step == \URL::getRequest()->path()) {
                return $next($request);
            }

            return redirect()->to($step);

        }

        return $next($request);
    }

    public function step(): string
    {
        $step = 'install/settings';
        if (app('installer')->hasOauthLicence() && app('installer')->isMigrated()) {
            $step = 'install/register';
        }

        if (Schema::hasTable('admins')) {
            if (Admin::count() > 0) {
                $step = 'install/summary';
            }
        }

        return $step;
    }
}
