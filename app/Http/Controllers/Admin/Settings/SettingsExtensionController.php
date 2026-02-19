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
            return $this->respondWithError(__('extensions.flash.already_enabled'));
        }
        try {
            $extensiondto = app('extension')->getExtension($type, $extension);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
        if (! empty($prerequisites)) {
            return $this->respondWithError(implode(', ', $prerequisites));
        }
        try {
            $extensiondto = app('extension')->getExtension($type, $extension);
            if (! $extensiondto->isActivable()) {
                return $this->respondWithError(__('extensions.flash.cannot_enable'));
            }
            app('extension')->enable($type, $extension);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
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

        return $this->respondWithSuccess(__('extensions.flash.enabled'));
    }

    public function disable(string $type, string $extension)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);
        if (! in_array($type, ['modules', 'addons', 'themes', 'email_templates', 'invoice_templates'])) {
            abort(404);
        }
        app('extension')->disable($type, $extension);
        ActionLog::log(ActionLog::EXTENSION_DISABLED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type]);

        return $this->respondWithSuccess(__('extensions.flash.disabled'));
    }

    public function clear()
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);
        \Artisan::call('cache:clear');

        return $this->respondWithSuccess(__('extensions.flash.cache_cleared'));
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

    public function uninstall(string $type, string $extension)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);

        if (! in_array($type, ['modules', 'addons', 'themes', 'email_templates', 'invoice_templates'])) {
            abort(404);
        }
        if (app('extension')->extensionIsEnabledForType($type, $extension)) {
            return $this->respondWithError(__('extensions.flash.uninstall_must_disable_first'));
        }
        try {
            // Rollback migrations before deleting extension files
            if (in_array($type, ['modules', 'addons'])) {
                $migrationPath = app('extension')->getMigrationPath($type, $extension);
                if (is_dir(base_path($migrationPath))) {
                    \Artisan::call('migrate:rollback', ['--force' => true, '--path' => $migrationPath]);
                }
            }
            app('extension')->uninstall($type, $extension);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
        \Artisan::call('config:clear');
        ActionLog::log(ActionLog::EXTENSION_UNINSTALLED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type]);

        return $this->respondWithSuccess(__('extensions.flash.uninstalled'));
    }

    public function bulkAction(\Illuminate\Http\Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);

        $validated = $request->validate([
            'extensions' => 'required|array|min:1',
            'extensions.*.type' => 'required|string|in:modules,addons,themes,email_templates,invoice_templates',
            'extensions.*.uuid' => 'required|string',
            'action' => 'required|string|in:enable,disable,install,update',
        ]);

        $results = [
            'success' => [],
            'errors' => [],
        ];

        foreach ($validated['extensions'] as $ext) {
            $type = $ext['type'];
            $uuid = $ext['uuid'];
            $action = $validated['action'];

            try {
                switch ($action) {
                    case 'enable':
                        if (app('extension')->extensionIsEnabled($uuid)) {
                            $results['errors'][] = ['uuid' => $uuid, 'message' => __('extensions.flash.already_enabled')];

                            continue 2;
                        }
                        $extensiondto = app('extension')->getExtension($type, $uuid);
                        if (! $extensiondto->isActivable()) {
                            $results['errors'][] = ['uuid' => $uuid, 'message' => __('extensions.flash.cannot_enable')];

                            continue 2;
                        }
                        app('extension')->enable($type, $uuid);
                        if ($type != 'themes') {
                            \Artisan::call('migrate', ['--force' => true, '--path' => $type.'/'.$uuid.'/database/migrations']);
                        }
                        ActionLog::log(ActionLog::EXTENSION_ENABLED, ExtensionDTO::class, $uuid, auth('admin')->id(), null, ['type' => $type]);
                        $results['success'][] = ['uuid' => $uuid, 'action' => 'enabled'];
                        break;

                    case 'disable':
                        app('extension')->disable($type, $uuid);
                        ActionLog::log(ActionLog::EXTENSION_DISABLED, ExtensionDTO::class, $uuid, auth('admin')->id(), null, ['type' => $type]);
                        $results['success'][] = ['uuid' => $uuid, 'action' => 'disabled'];
                        break;

                    case 'install':
                    case 'update':
                        app('extension')->update($type, $uuid);
                        ActionLog::log(ActionLog::EXTENSION_UPDATED, ExtensionDTO::class, $uuid, auth('admin')->id(), null, ['type' => $type]);
                        $results['success'][] = ['uuid' => $uuid, 'action' => $action === 'install' ? 'installed' : 'updated'];
                        break;
                }
            } catch (\Exception $e) {
                $results['errors'][] = ['uuid' => $uuid, 'message' => $e->getMessage()];
            }
        }

        // Clear caches after bulk operations
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
        \Artisan::call('config:clear');
        \Artisan::call('db:seed', ['--force' => true]);

        $successCount = count($results['success']);
        $errorCount = count($results['errors']);

        if ($errorCount === 0) {
            return response()->json([
                'success' => true,
                'message' => __('extensions.bulk.success'),
                'results' => $results,
            ]);
        }

        return response()->json([
            'success' => $successCount > 0,
            'message' => __('extensions.bulk.partial_success', ['success' => $successCount, 'failed' => $errorCount]),
            'results' => $results,
        ], $successCount > 0 ? 200 : 400);
    }

    private function respondWithSuccess(string $message)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->back()->with('success', $message);
    }

    private function respondWithError(string $message)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => false, 'error' => $message], 400);
        }

        return redirect()->back()->with('error', $message);
    }
}
