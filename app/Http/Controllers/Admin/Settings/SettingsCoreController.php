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
namespace App\Http\Controllers\Admin\Settings;

use App\Helpers\EnvEditor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\AppSettingsRequest;
use App\Mail\MailTested;
use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use App\Services\Core\LocaleService;
use Illuminate\Http\Request;

class SettingsCoreController extends Controller
{
    public function showEmailSettings()
    {
        return view('admin.settings.core.email');
    }

    public function showAppSettings()
    {
        $locales = LocaleService::getLocalesNames();
        $timezones = collect(timezone_identifiers_list())->mapWithKeys(fn ($timezone) => [$timezone => $timezone])->toArray();

        return view('admin.settings.core.app', compact('locales', 'timezones'));
    }

    public function showMaintenanceSettings()
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);

        return view('admin.settings.core.maintenance');
    }

    public function storeMaintenanceSettings(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        $data = $this->validate($request, [
            'maintenance_enabled' => 'nullable',
            'maintenance_url' => 'required|string|max:255',
            'maintenance_message' => 'required|string|max:1000',
            'maintenance_button_text' => 'nullable|string|max:255',
            'maintenance_button_icon' => 'nullable|string|max:255',
            'maintenance_button_url' => 'nullable|string|url|max:1000',
        ]);
        $data['maintenance_enabled'] = $data['maintenance_enabled'] ?? false;
        Setting::updateSettings($data);

        return redirect()->back()->with('success', __('maintenance.settings.success'));
    }

    public function storeEmailSettings(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        $data = $this->validate($request, [
            'mail_from_address' => 'required|string|max:255',
            'mail_from_name' => 'required|string|max:255',
            'mail_salutation' => 'required|string|max:255',
            'mail_greeting' => 'required|string|max:255',
            'mail_domain' => 'required|string|max:255',
        ]);
        if ($request->has('mail_smtp_enable') || ($request->filled('mail_smtp_host'))) {
            $data['mail_smtp_enable'] = true;
            if (! $request->has('mail_smtp_enable') && getenv('MAIL_MAILER') == 'smtp') {
                $data['mail_smtp_enable'] = false;
            }
            $data = $data + $this->validate($request, [
                'mail_smtp_host' => 'required|string|max:1000',
                'mail_smtp_port' => 'required|integer|between:1,65535',
                'mail_smtp_username' => 'string|nullable|max:1000',
                'mail_smtp_password' => 'string|nullable|max:1000',
                'mail_smtp_encryption' => 'required|string|in:tls,ssl,none',
            ]);
            EnvEditor::updateEnv([
                'MAIL_HOST' => $data['mail_smtp_host'],
                'MAIL_PORT' => $data['mail_smtp_port'],
                'MAIL_USERNAME' => $data['mail_smtp_username'],
                'MAIL_PASSWORD' => $data['mail_smtp_password'],
                'MAIL_ENCRYPTION' => $data['mail_smtp_encryption'],
            ]);
        } else {
            $data['mail_smtp_enable'] = false;
        }
        $mailer = $data['mail_smtp_enable'] == 1 ? 'smtp' : 'sendmail';
        if (array_key_exists('mail_disable_mail', $request->all())) {
            $mailer = 'log';
        }
        EnvEditor::updateEnv([
            'MAIL_MAILER' => $mailer,
            'MAIL_FROM_ADDRESS' => $data['mail_from_address'],
            'MAIL_FROM_NAME' => $data['mail_from_name'],
            'APP_URL' => $data['mail_domain'],
        ]);
        Setting::updateSettings($request->only('mail_greeting', 'mail_salutation'));

        return redirect()->back()->with('success', __('admin.settings.core.mail.success'));
    }

    public function testmail(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        try {
            $request->user('admin')->notify(new MailTested($request->user('admin')));
        } catch (\Exception $exception) {
            return response($exception->getMessage(), 500);
        }

        return response('', 204);
    }

    public function storeAppSettings(AppSettingsRequest $request)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        $data = $request->validated();
        if ($request->hasFile('app_logo')) {
            if (\setting('app_logo') && \Storage::exists(\setting('app_logo'))) {
                \Storage::delete(\setting('app_logo'));
            }
            $file = $request->file('app_logo')->storeAs('public', 'app_logo'.rand(1000, 9999).'.png');
            $data['app_logo'] = $file;
        }
        if ($request->hasFile('app_favicon')) {
            if (\setting('app_favicon') && \Storage::exists(\setting('app_favicon'))) {
                \Storage::delete(\setting('app_favicon'));
            }
            $file = $request->file('app_favicon')->storeAs('public', 'app_favicon'.rand(1000, 9999).'.png');
            $data['app_favicon'] = $file;
        }
        if ($request->hasFile('app_logo_text')) {
            if (\setting('app_logo_text') && \Storage::exists(\setting('app_logo_text'))) {
                \Storage::delete(\setting('app_logo_text'));
            }
            $file = $request->file('app_logo_text')->storeAs('public', 'app_logo_text'.rand(1000, 9999).'.png');
            $data['app_logo_text'] = $file;
        }

        if ($request->remove_app_logo == 'true') {
            if (\setting('app_logo') && \Storage::exists(\setting('app_logo'))) {
                \Storage::delete(\setting('app_logo'));
            }
            $data['app_logo'] = null;
            unset($data['remove_app_logo']);
        }
        if ($request->remove_app_favicon == 'true') {
            if (\setting('app_favicon') && \Storage::exists(\setting('app_favicon'))) {
                \Storage::delete(\setting('app_favicon'));
            }
            $data['app_favicon'] = null;
            unset($data['remove_app_favicon']);
        }
        if ($request->remove_app_logo_text == 'true') {
            if (\setting('app_logo_text') && \Storage::exists(\setting('app_logo_text'))) {
                \Storage::delete(\setting('app_logo_text'));
            }
            $data['app_logo_text'] = null;
            unset($data['remove_app_logo_text']);
        }
        EnvEditor::updateEnv([
            'APP_NAME' => $data['app_name'],
            'APP_ENV' => $data['app_env'] == 'production' ? 'production' : 'local',
            'APP_DEBUG' => $data['app_debug'] == 'true' ? 'true' : 'false',
            'TELEMETRY_ENABLED' => $data['app_telemetry'] ?? 'false'
        ]);
        unset($data['app_env']);
        unset($data['app_debug']);
        unset($data['app_telemetry']);
        Setting::updateSettings($data);

        return redirect()->back()->with('success', __('admin.settings.core.app.success'));
    }
}
