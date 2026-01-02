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

namespace App\Http\Controllers\Admin\Helpdesk;

use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;

class HelpdeskSettingsController extends \App\Http\Controllers\Controller
{
    public function showSettings()
    {
        return view('admin.settings.helpdesk.settings');
    }

    public function storeSettings(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        $data = $request->validate([
            'helpdesk_ticket_auto_close_days' => 'required|integer|min:0',
            'helpdesk_attachments_max_size' => 'required|integer|min:1',
            'helpdesk_attachments_allowed_types' => 'required|string',
            'helpdesk_webhook_url' => 'nullable|url',
            'helpdesk_reopen_days' => 'required|integer|min:-1',
        ]);
        $data['helpdesk_allow_attachments'] = $request->has('helpdesk_allow_attachments');
        Setting::updateSettings($data);

        return back()->with('success', __('helpdesk.admin.settings.success'));
    }
}
