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
namespace App\Http\Controllers\Admin\Settings;

use App\Helpers\EnvEditor;
use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\RequiredIf;

class SettingsSecurityController
{
    public function showSecuritySettings()
    {
        $drivers = [
            'argon' => 'Argon - For Migrated instances',
            'bcrypt' => 'Bcrypt',
            'argon2id' => 'Argon2id',
        ];
        $captcha = [
            'none' => 'None',
            'recaptcha' => 'Google reCAPTCHA',
            'hcaptcha' => 'hCaptcha',
            'cloudflare' => 'Cloudflare turnstile',
        ];

        return view('admin.settings.core.security', compact('drivers', 'captcha'));
    }

    public function storeSecurity(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        $data = $request->validate([
            'hash_driver' => 'required|string',
            'allow_reset_password' => 'nullable|string|in:true,false',
            'allow_registration' => 'nullable|string|in:true,false',
            'auto_confirm_registration' => 'nullable|string|in:true,false',
            'force_login_client' => 'nullable|string|in:true,false',
            'allow_plus_in_email' => 'nullable|string|in:true,false',
            'password_timeout' => 'nullable|integer',
            'banned_emails' => 'nullable|string',
            'captcha_driver' => 'required|string',
            'admin_prefix' => 'required|string',
            'captcha_site_key' => [new RequiredIf($request->captcha_driver != 'none')],
            'captcha_secret_key' => [new RequiredIf($request->captcha_driver != 'none')],
            'gdrp_cookies_privacy_link' => ['nullable', 'string', 'url'],
        ]);
        EnvEditor::updateEnv([
            'HASH_DRIVER' => $data['hash_driver'],
            'ADMIN_PREFIX' => $data['admin_prefix'],
        ]);
        $data['allow_reset_password'] = $data['allow_reset_password'] ?? 'false';
        $data['allow_registration'] = $data['allow_registration'] ?? 'false';
        $data['auto_confirm_registration'] = $data['auto_confirm_registration'] ?? 'false';
        $data['force_login_client'] = $data['force_login_client'] ?? 'false';
        $data['allow_plus_in_email'] = $data['allow_plus_in_email'] ?? 'false';
        Setting::updateSettings($data);

        return redirect()->back()->with('success', __('admin.settings.core.security.success'));
    }
}
