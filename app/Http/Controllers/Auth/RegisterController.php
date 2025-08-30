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


namespace App\Http\Controllers\Auth;

use App\Helpers\Countries;
use App\Http\Controllers\Controller;

class RegisterController extends Controller
{
    public function showForm()
    {
        if (setting('allow_registration', true) === false) {
            return back()->with('error', __('auth.register.error_registration_disabled'));
        }
        if (app('extension')->extensionIsEnabled('socialauth')) {
            $providers = \App\Addons\SocialAuth\Models\ProviderEntity::where('enabled', true)->get();
        } else {
            $providers = collect([]);
        }

        return view('front.auth.register', ['countries' => Countries::names(), 'providers' => $providers]);
    }
}
