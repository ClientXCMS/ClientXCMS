<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Date;

class RequireAdminPassword extends RequirePassword
{
    // Sibling of the Laravel key, never a child of it: the session reads dots as nesting and would turn the native key into an array.
    public const SESSION_KEY = 'auth.admin_password_confirmed_at';

    public static function confirm(Session $session): void
    {
        $session->put(self::SESSION_KEY, Date::now()->unix());
    }

    public function handle($request, Closure $next, $redirectToRoute = null, $passwordTimeoutSeconds = null)
    {
        return parent::handle($request, $next, $redirectToRoute ?: 'admin.password.confirm', $passwordTimeoutSeconds);
    }

    protected function shouldConfirmPassword($request, $passwordTimeoutSeconds = null)
    {
        $confirmedAt = Date::now()->unix() - $request->session()->get(self::SESSION_KEY, 0);

        // The container binds the timeout on the parent class only, so it is read here instead of $this->passwordTimeout.
        return $confirmedAt > ($passwordTimeoutSeconds ?? config('auth.password_timeout', 10800));
    }
}
