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

use App\DTO\Core\Extensions\ExtensionDTO;
use App\Models\ActionLog;
use App\Models\Admin\Permission;

class SettingsExtensionController
{
    public function showExtensions()
    {
        $groups = app('extension')->getGroupsWithExtensions();

        $card = app('settings')->getCards()->firstWhere('uuid', 'extensions');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'extensions');
        \View::share('current_card', $card);
        \View::share('current_item', $item);

        return view('admin.settings.extensions.index', ['groups' => $groups, 'tags' => app('extension')->fetch()['tags'] ?? []]);
    }

    public function enable(string $type, string $extension)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);

        if (! in_array($type, ['modules', 'addons', 'themes', 'email_templates', 'invoice_templates'])) {
            abort(404);
        }
        if (app('extension')->extensionIsEnabled($extension)) {
            return redirect()->back()->with('error', __('extensions.flash.already_enabled'));
        }
        try {
            $extensiondto = app('extension')->getExtension($type, $extension);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        if (! empty($prerequisites)) {
            return redirect()->back()->with('error', implode(', ', $prerequisites));
        }
        try {
            $extensiondto = app('extension')->getExtension($type, $extension);
            if (! $extensiondto->isActivable()) {
                return redirect()->back()->with('error', __('extensions.flash.cannot_enable'));
            }
            app('extension')->enable($type, $extension);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
        \Artisan::call('config:clear');
        \Artisan::call('db:seed', ['--force' => true]);
        if ($type == 'themes') {
            \App\Theme\ThemeManager::clearCache();
            ActionLog::log(ActionLog::THEME_CHANGED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type]);
        } else {
            \Artisan::call('migrate', ['--force' => true, '--path' => $type.'/'.$extension.'/database/migrations']);
            ActionLog::log(ActionLog::EXTENSION_ENABLED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type]);
        }

        return redirect()->back()->with('success', __('extensions.flash.enabled'));
    }

    public function disable(string $type, string $extension)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);
        if (! in_array($type, ['modules', 'addons', 'themes', 'email_templates', 'invoice_templates'])) {
            abort(404);
        }
        app('extension')->disable($type, $extension);
        ActionLog::log(ActionLog::EXTENSION_DISABLED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type]);

        return redirect()->back()->with('success', __('extensions.flash.disabled'));
    }

    public function clear()
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);
        \Artisan::call('cache:clear');

        return redirect()->back()->with('success', __('extensions.flash.cache_cleared'));
    }

    public function update(string $type, string $extension)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);
        if (! in_array($type, ['modules', 'addons', 'themes', 'email_templates', 'invoice_templates'])) {
            abort(404);
        }
        try {
            app('extension')->update($type, $extension);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
        ActionLog::log(ActionLog::EXTENSION_UPDATED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type]);

        return response()->json(['success' => __('extensions.flash.updated')]);
    }
}
