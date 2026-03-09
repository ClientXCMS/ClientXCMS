<?php

namespace App\Addons\HelpdeskBridge\Http\Controllers\Admin;

use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;

class SettingsController extends \App\Http\Controllers\Controller
{
    public function show()
    {
        return view('helpdesk-bridge_admin::settings');
    }

    public function store(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);

        $data = $request->validate([
            'helpdesk_reply_mailbox' => 'required|string|max:64|regex:/^[a-zA-Z0-9._+-]+$/',
            'helpdesk_inbound_webhook_token' => 'required|string|min:16|max:128',
            'helpdesk_mail_fromaddress' => 'nullable|email|max:255',
            'helpdesk_mail_fromname' => 'nullable|string|max:255',
            'helpdesk_mail_smtp_host' => 'nullable|string|max:255',
            'helpdesk_mail_smtp_port' => 'nullable|integer|min:1|max:65535',
            'helpdesk_mail_smtp_username' => 'nullable|string|max:255',
            'helpdesk_mail_smtp_password' => 'nullable|string|max:255',
            'helpdesk_mail_smtp_encryption' => 'nullable|string|in:tls,ssl,null,',
        ]);

        $data['helpdesk_smtp_enable'] = $request->has('helpdesk_smtp_enable');
        Setting::updateSettings($data);

        return back()->with('success', 'Configuration Helpdesk Bridge mise à jour.');
    }
}
