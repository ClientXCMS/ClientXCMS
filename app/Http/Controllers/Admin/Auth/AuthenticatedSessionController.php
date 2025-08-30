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


namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Models\Admin\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException $e) {
            if ($request->has('redirect')) {
                return redirect()->away($request->get('redirect'))->with('error', $e->getMessage())->withErrors($e->getMessage());
            }
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            } else {
                throw $e;
            }
        }

        $request->session()->regenerate();
        if ($request->has('redirect')) {
            return redirect()->away($request->get('redirect'));
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function confirmPassword(Request $request)
    {
        return view('admin.auth.confirm-password');
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);
        $hash = \Hash::driver('bcrypt');
        if (! $hash->check($request->password, $request->user('admin')->password)) {
            return back()->withErrors([
                'password' => [__('auth.password')],
            ]);
        }
        $request->session()->passwordConfirmed();

        return redirect()->intended();
    }

    public function autologin(Request $request, string $id, string $token)
    {
        if (! Auth::guard('admin')->guest()) {
            return redirect()->route('admin.dashboard');
        }
        if (! Admin::where('id', $id)->exists()) {
            return redirect()->route('admin.login');
        }
        if (! $request->hasValidSignature()) {
            return redirect()->route('admin.login');
        }
        $admin = Admin::findOrFail($id);
        if ($admin->getMetadata('autologin_key') !== $token) {
            return redirect()->route('admin.login');
        }
        if ($admin->getMetadata('autologin_expires_at') < now()) {
            $admin->forgetMetadata('autologin_key');
            $admin->forgetMetadata('autologin_expires_at');

            return redirect()->route('admin.login');
        }
        if ($admin->getMetadata('autologin_unique')) {
            $admin->forgetMetadata('autologin_key');
            $admin->forgetMetadata('autologin_expires_at');
        }
        \Session::put('autologin', true);
        Auth::guard('admin')->login($admin);

        return redirect()->route('admin.dashboard')->with('success', __('admin.dashboard.autologin_success', ['name' => $admin->name]));
    }
}
