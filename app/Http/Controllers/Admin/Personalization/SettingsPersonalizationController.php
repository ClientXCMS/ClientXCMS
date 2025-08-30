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

use App\Exceptions\LicenseInvalidException;
use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use App\Models\Personalization\MenuLink;
use App\Theme\ThemeManager;
use Illuminate\Http\Request;

class SettingsPersonalizationController extends Controller
{
    public function showFrontMenu()
    {
        $menus = MenuLink::where('type', 'front')->whereNull('parent_id')->orderBy('position')->get();

        return view('admin.personalization.settings.front', [
            'menus' => $menus,
        ]);
    }

    public function showCustomMenu(string $type)
    {
        $menus = MenuLink::where('type', $type)->whereNull('parent_id')->orderBy('position')->get();

        $card = app('settings')->getCards()->firstWhere('uuid', 'personalization');
        if (! $card) {
            abort(404);
        }
        return view('admin.personalization.settings.custom', [
            'menus' => $menus,
            'type' => $type,
            'current_card' => $card,
        ]);
    }

    public function showBottomMenu()
    {
        $menus = MenuLink::where('type', 'bottom')->whereNull('parent_id')->orderBy('position')->get();

        return view('admin.personalization.settings.bottom', [
            'menus' => $menus,
        ]);
    }

    public function storeBottomMenu(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_PERSONALIZATION);
        $this->validate($request, [
            'theme_footer_description' => ['required', 'string', 'max:1000', new \App\Rules\NoScriptOrPhpTags()],
            'theme_footer_topheberg' => ['nullable', 'string', 'max:1000', new \App\Rules\NoScriptOrPhpTags()],
        ]);
        Setting::updateSettings([
            'theme_footer_description' => $request->get('theme_footer_description'),
            'theme_footer_topheberg' => $request->get('theme_footer_topheberg'),
        ]);

        return redirect()->back();
    }

    public function storeSeoSettings(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        $data = $this->validate($request, [
            'seo_headscripts' => 'nullable|string|max:65535',
            'seo_description' => 'nullable|string|max:1000',
            'seo_keywords' => 'nullable|string|max:1000',
            'seo_footscripts' => 'nullable|string|max:65535',
            'seo_themecolor' => 'nullable|string|max:1000',
            'seo_disablereferencement' => 'in:true,false',
            'seo_site_title' => 'required|string|max:1000',
        ]);
        $data['seo_disablereferencement'] = $data['seo_disablereferencement'] ?? 'false';
        Setting::updateSettings($data);
        \Cache::delete('seo_head');
        \Cache::delete('seo_footer');

        return redirect()->back()->with('success', __('personalization.seo.success'));
    }

    public function showSeoSettings()
    {
        return view('admin.personalization.settings.seo');
    }

    public function showHomeSettings()
    {
        return view('admin.personalization.settings.home');
    }

    public function storeHomeSettings(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_PERSONALIZATION);
        $data = $this->validate($request, [
            'theme_home_title' => 'required|string|max:255',
            'theme_home_subtitle' => 'required|string|max:255',
            'theme_home_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'theme_home_enabled' => 'in:true,false',
            'theme_home_title_meta' => 'required|string|max:255',
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
        $data['theme_home_enabled'] = $data['theme_home_enabled'] ?? 'false';
        Setting::updateSettings($data);

        return redirect()->back()->with('success', __('personalization.home.success'));
    }

    public function showPrimaryColors()
    {
        $theme = ThemeManager::getColorsArray();
        $primary_color = $theme['400'];
        $secondary_color = $theme['600'];

        return view('admin.personalization.settings.primary', [
            'primary_color' => $primary_color,
            'secondary_color' => $secondary_color,
        ]);
    }

    public function storePrimaryColors(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_PERSONALIZATION);
        $this->validate($request, [
            'theme_primary' => 'required|string|max:7',
            'theme_secondary' => 'required|string|max:7',
        ]);
        $file = storage_path('app'.DIRECTORY_SEPARATOR.'theme.json');
        $theme = [
            '50' => '#f0f5ff',
            '100' => '#e5edff',
            '200' => '#cddbfe',
            '300' => '#b4c6fc',
            '400' => $request->get('theme_secondary'),
            '500' => '#6875f5',
            '600' => $request->get('theme_primary'),
            '700' => $request->get('theme_primary'),
            '800' => '#42389d',
            '900' => '#362f78',
        ];
        file_put_contents($file, json_encode($theme));
        Setting::updateSettings([
            'theme_switch_mode' => $request->get('theme_switch_mode'),
            'theme_header_logo' => $request->get('theme_header_logo') ?? 'false',
        ]);
        try {
            app('license')->restartNPM();
        } catch (LicenseInvalidException $e) {
            \Session::flash('error', "Error in restart NPM : " . $e->getMessage());
        }

        return redirect()->back()->with('success', __('personalization.config.success'));
    }
}
