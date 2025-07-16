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
namespace App\Http\Controllers;

use App\Exceptions\LicenseInvalidException;
use App\Models\Admin\Admin;
use App\Models\Admin\Role;
use App\Services\TelemetryService;
use Illuminate\Http\Request;

class InstallController extends Controller
{
    public function showSettings()
    {
        \Session::flash('info', __('install.settings.detecteddomain', ['domain' => request()->getHttpHost()]));
        $isMigrated = app('installer')->isMigrated();
        if (! $isMigrated) {
            \Session::flash('error', __('install.settings.migrationwarning'));
        }

        return view('install.settings', ['step' => 1, 'isMigrated' => $isMigrated])->with('info', __('install.settings.infotext'));
    }

    public function storeSettings(Request $request)
    {
        $this->validate($request, [
            'app_name' => 'required|string|max:255',
            'client_id' => 'required|integer',
            'client_secret' => 'required|string',
        ]);
        app('installer')->updateEnv([
            'APP_NAME' => $request->input('app_name'),
            'OAUTH_CLIENT_ID' => $request->input('client_id'),
            'OAUTH_CLIENT_SECRET' => $request->input('client_secret'),
        ]);

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
        Admin::insert($data);
        if ($data['send_telemetry'] ?? false) {
            app(TelemetryService::class)->sendTelemetry();
        }

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
