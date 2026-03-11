<?php

namespace App\Addons\HelpdeskBridge\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);

        return view('helpdesk-bridge_admin::settings');
    }

    public function update(Request $request): RedirectResponse
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);

        $data = $request->validate([
            'helpdesk_reply_mailbox' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9._-]+$/i'],
            'helpdesk_inbound_webhook_token' => ['required', 'string', 'min:16', 'max:255'],
            'helpdesk_bridge_create_ticket_from_inbound' => ['nullable', 'boolean'],
        ]);

        $data['helpdesk_bridge_create_ticket_from_inbound'] = $request->boolean('helpdesk_bridge_create_ticket_from_inbound');

        Setting::updateSettings($data);

        return back()->with('success', __('helpdesk-bridge::helpdesk_bridge.admin.settings.success'));
    }
}
