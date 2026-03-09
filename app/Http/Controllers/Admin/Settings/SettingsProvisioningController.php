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

use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;

class SettingsProvisioningController extends \App\Http\Controllers\Controller
{
    public function showServicesSettings()
    {
        $variables = ['%serviceid%', '%servicename%', '%customeremail%', '%customername%', '%serviceurl%'];

        return view('admin.provisioning.settings.services', compact('variables'));
    }

    public function storeServicesSettings(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        $data = $this->validate($request, [
            'days_before_creation_invoice_renewal' => 'required|integer|min:1',
            'days_before_expiration' => 'required|integer|min:1',
            'services_suspend_after_unpaid_days' => 'required|integer|min:0|max:365',
            'services_renewal_grace_days' => 'required|integer|min:0|max:365',
            'services_late_fee_until_days' => 'required|integer|min:0|max:365',
            'services_expire_and_delete_after_days' => 'required|integer|min:1|max:3650',
            'webhook_renewal_url' => 'nullable|url',
            'notifications_expiration_days' => 'nullable|string',
            'max_subscription_tries' => 'required|integer|min:0',
        ]);
        Setting::updateSettings($data);

        return redirect()->back()->with('success', __('provisioning.admin.settings.services.success'));
    }
}
