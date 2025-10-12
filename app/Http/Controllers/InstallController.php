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


namespace App\Http\Controllers;

use App\Exceptions\LicenseInvalidException;
use App\Helpers\EnvEditor;
use App\Models\Admin\Admin;
use App\Models\Admin\Role;
use App\Models\Admin\Setting;
use App\Services\Core\LocaleService;
use App\Services\TelemetryService;
use Illuminate\Http\Request;

class InstallController extends Controller
{
    public function showSettings()
    {
        $isMigrated = app('installer')->isMigrated();
        if (! $isMigrated) {
            \Session::flash('error', __('install.settings.migrationwarning'));
        }
        \Session::flash('info', __('install.settings.detecteddomain', ['domain' => request()->getHttpHost()]));
        $locales = collect(LocaleService::getLocales(false, false))->mapWithKeys(function ($item, $key) {
            return [$key => $item['name']];
        })->toArray();
        return view('install.settings', ['step' => 1, 'isMigrated' => $isMigrated, 'locales' => $locales]);
    }

    public function storeSettings(Request $request)
    {
        $this->validate($request, [
            'app_name' => 'required|string|max:255',
            'client_id' => 'required|integer',
            'client_secret' => 'required|string',
            'locales' => 'required|array',
            'locales.*' => 'string|size:5',
        ]);
        app('installer')->updateEnv([
            'APP_NAME' => $request->input('app_name'),
            'OAUTH_CLIENT_ID' => $request->input('client_id'),
            'OAUTH_CLIENT_SECRET' => $request->input('client_secret'),
        ]);

        foreach ($request->input('locales') as $locale) {
            LocaleService::downloadFiles($locale);
        }
        Setting::updateSettings(['app_default_locale' => $request->input('locales')[0] ?? 'en_GB']);
        return redirect()->to(app('license')->getAuthorizationUrl());
    }

    public function showRegister()
    {
        $isMigration = app('installer')->isMigrated();
        if (! $isMigration) {
            return redirect()->to(route('install.settings'));
        }

        return view('install.register', ['step' => 2]);
    }

    public function storeRegister(Request $request)
    {
        $data = $this->validate($request, [
            'email' => 'required|email|max:255',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'send_telemetry' => 'nullable',
        ]);
        $data['password'] = bcrypt($data['password']);
        $data['username'] = $data['firstname'].' '.$data['lastname'];
        $data['role_id'] = Role::first()->id;
        $data['email'] = strtolower($data['email']);
        if (Admin::first()) {
            return redirect()->to(route('install.summary'));
        }
        Admin::insert($request->only(['email', 'firstname', 'lastname', 'password', 'role_id']) + ['username' => $data['username']]);
        $data['send_telemetry'] = array_key_exists('send_telemetry', $data) ? true : false;
        if ($data['send_telemetry']) {
            app(TelemetryService::class)->sendTelemetry();
        }
        EnvEditor::updateEnv(['TELEMETRY_DISABLED' => $data['send_telemetry'] ? 'false' : 'true']);

        return redirect()->to(route('install.summary'));
    }

    public function showSummary()
    {
        try {
            $modules = app('license')->getLicense()->getFormattedExtensions();
            $theme = app('theme')->getTheme()->name;
        } catch (LicenseInvalidException $e) {
            return redirect()->to(app('license')->getAuthorizationUrl());
        }

        return view('install.summary', ['step' => 4, 'theme' => $theme, 'email' => Admin::first()->email, 'modules' => $modules]);
    }

    public function storeSummary(Request $request)
    {
        auth('admin')->loginUsingId(Admin::first()->id);
        app('installer')->createInstalledFile();

        return redirect()->to(route('admin.dashboard'));
    }
}
