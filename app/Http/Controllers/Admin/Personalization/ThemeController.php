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


namespace App\Http\Controllers\Admin\Personalization;

use App\Models\Admin\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;

class ThemeController extends \App\Http\Controllers\Controller
{
    public function showTheme()
    {
        staff_aborts_permission(\App\Models\Admin\Permission::MANAGE_PERSONALIZATION);
        $errors = session('errors', new ViewErrorBag);
        $theme = app('theme')->getTheme();
        $context = [
            'configHTML' => $theme->configView(['errors' => $errors]),
            'currentTheme' => $theme,
            'modes' => [
                'light' => __('personalization.theme.fields.theme_switch_mode.light'),
                'dark' => __('personalization.theme.fields.theme_switch_mode.dark'),
                'both' => __('personalization.theme.fields.theme_switch_mode.both'),
            ],
        ];

        return view('admin.personalization.settings.theme', $context);
    }

    public function configTheme(Request $request)
    {
        staff_aborts_permission(\App\Models\Admin\Permission::MANAGE_PERSONALIZATION);
        $theme = app('theme')->getTheme();
        $data = $request->validate([
            'theme_home_title' => 'required|string',
            'theme_home_subtitle' => 'required|string',
            'theme_home_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'theme_switch_mode' => 'required|in:light,dark,both',
            'theme_header_logo' => 'in:true,false',
        ]);
        if ($request->hasFile('theme_home_image')) {
            if (\setting('theme_home_image') && \Storage::exists(\setting('theme_home_image'))) {
                \Storage::delete(\setting('theme_home_image'));
            }
            $file = 'home.'.$request->file('theme_home_image')->getClientOriginalExtension();
            $file = $request->file('theme_home_image')->storeAs('public'.DIRECTORY_SEPARATOR.'uploads', $file);
            $data['theme_home_image'] = $file;
        }
        if ($request->remove_theme_home_image == 'true') {
            if (\setting('theme_home_image') && \Storage::exists(\setting('theme_home_image'))) {
                \Storage::delete(\setting('theme_home_image'));
            }
            $data['theme_home_image'] = null;
            unset($data['remove_theme_home_image']);
        }
        Setting::updateSettings($data);

        try {
            $theme->storeConfig($request->all());

            return redirect()->back()->with('success', __('personalization.config.success'));
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator);
        }
    }
}
