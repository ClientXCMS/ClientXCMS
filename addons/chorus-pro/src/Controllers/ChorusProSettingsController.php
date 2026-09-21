<?php

namespace App\Addons\ChorusPro\Controllers;

use App\Addons\ChorusPro\ChorusProExchangeProvider;
use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ChorusProSettingsController extends Controller
{
    public function edit(ChorusProExchangeProvider $provider)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);

        return view('chorus-pro::settings', ['configured' => $provider->isConfigured()]);
    }

    public function update(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        $data = $request->validate([
            'chorus_pro_enabled' => 'nullable|in:true,false',
            'chorus_pro_environment' => 'required|in:qualification,production',
            'chorus_pro_client_id' => 'nullable|string|max:1000',
            'chorus_pro_client_secret' => 'nullable|string|max:2000',
            'chorus_pro_account' => 'nullable|string|max:2000',
            'chorus_pro_api_url' => 'required|url|max:1000',
            'chorus_pro_oauth_url' => 'required|url|max:1000',
            'chorus_pro_timeout' => 'required|integer|min:1|max:120',
        ]);
        $data['chorus_pro_enabled'] = $data['chorus_pro_enabled'] ?? 'false';
        foreach (['chorus_pro_client_id', 'chorus_pro_client_secret', 'chorus_pro_account'] as $secret) {
            if (blank($data[$secret] ?? null)) {
                unset($data[$secret]);
            } else {
                $data[$secret] = Crypt::encryptString($data[$secret]);
            }
        }
        Setting::updateSettings($data);

        return back()->with('success', __('chorus-pro::messages.settings.saved'));
    }

    public function test(ChorusProExchangeProvider $provider)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        try {
            $provider->diagnostic();

            return back()->with('success', __('chorus-pro::messages.settings.connection_ok'));
        } catch (\Throwable $exception) {
            return back()->withErrors(['connection' => $exception->getMessage()]);
        }
    }
}
