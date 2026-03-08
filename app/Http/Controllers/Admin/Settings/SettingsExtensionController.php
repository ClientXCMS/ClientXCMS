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
use App\Extensions\ExtensionManager;
use App\Models\ActionLog;
use App\Models\Admin\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

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
            $prerequisites = app('extension')->checkPrerequisitesForEnable($type, $extension);
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
            app('extension')->uninstall($type, $extension);
        } catch (\Exception $e) {
            \Log::error('Extension uninstall failed', ['extension' => $extension, 'type' => $type, 'error' => $e->getMessage()]);

            return $this->respondWithError(__('extensions.flash.uninstall_failed'));
        }
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
        \Artisan::call('config:clear');
        ActionLog::log(ActionLog::EXTENSION_UNINSTALLED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type]);

        return $this->respondWithSuccess(__('extensions.flash.uninstalled'));
    }
    public function importZip(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);

        $validated = $request->validate([
            'extension_zip' => 'required|file|mimes:zip|max:51200',
            'extension_type' => 'required|string|in:modules,addons,themes',
            'extension_checksum' => 'nullable|string|size:64',
        ]);

        $lock = Cache::lock('extensions:import', 120);
        if (! $lock->get()) {
            return $this->respondWithError(__('features.extensions.import_lock'));
        }

        try {
            $uploadedFile = $validated['extension_zip'];
            $zipPath = $uploadedFile->getRealPath();

            if (! empty($validated['extension_checksum'])) {
                $hash = hash_file('sha256', $zipPath);
                if (! hash_equals(strtolower($validated['extension_checksum']), strtolower($hash))) {
                    return $this->respondWithError(__('features.extensions.checksum_invalid'));
                }
            }

            $zip = new \ZipArchive;
            if ($zip->open($zipPath) !== true) {
                return $this->respondWithError("Impossible de lire l'archive ZIP.");
            }

            $maxFiles = 4000;
            $maxUncompressedSize = 250 * 1024 * 1024;
            $totalUncompressed = 0;

            if ($zip->numFiles > $maxFiles) {
                $zip->close();

                return $this->respondWithError(__('features.extensions.zip_too_many_files'));
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $zip->getNameIndex($i);

                if (str_starts_with($name, '/') || str_contains($name, '..')) {
                    $zip->close();

                    return $this->respondWithError("Archive ZIP invalide.");
                }

                $totalUncompressed += (int) ($stat['size'] ?? 0);
                if ($totalUncompressed > $maxUncompressedSize) {
                    $zip->close();

                    return $this->respondWithError(__('features.extensions.zip_too_large_uncompressed'));
                }
            }

            $tmpRoot = storage_path('app/tmp/extensions-import-'.uniqid());
            File::ensureDirectoryExists($tmpRoot);
            $zip->extractTo($tmpRoot);
            $zip->close();

            $entries = collect(File::directories($tmpRoot));
            if ($entries->count() !== 1) {
                File::deleteDirectory($tmpRoot);

                return $this->respondWithError(__('features.extensions.zip_invalid_single_folder'));
            }

            $extensionPath = $entries->first();
            $extension = basename($extensionPath);
            $type = $validated['extension_type'];

            if ($type === 'themes' && ! File::exists($extensionPath.'/theme.json')) {
                File::deleteDirectory($tmpRoot);

                return $this->respondWithError("Le ZIP ne ressemble pas à un thème valide.");
            }

            if (in_array($type, ['modules', 'addons']) && ! File::exists($extensionPath.'/composer.json')) {
                File::deleteDirectory($tmpRoot);

                return $this->respondWithError("Le ZIP doit contenir un composer.json valide.");
            }

            $destination = $type === 'themes'
                ? base_path('resources/themes/'.$extension)
                : base_path($type.'/'.$extension);

            $backupPath = null;

            try {
                if (File::isDirectory($destination)) {
                    $backupPath = storage_path('app/extensions-backups/'.$type.'-'.$extension.'-'.date('YmdHis'));
                    File::ensureDirectoryExists(dirname($backupPath));
                    File::copyDirectory($destination, $backupPath);
                    File::deleteDirectory($destination);
                }

                File::ensureDirectoryExists(dirname($destination));
                File::copyDirectory($extensionPath, $destination);

                $extensions = ExtensionManager::readExtensionJson();
                $existing = collect($extensions[$type] ?? [])->firstWhere('uuid', $extension);
                if (! $existing) {
                    $extensions[$type][] = [
                        'uuid' => $extension,
                        'version' => 'v1.0',
                        'type' => $type,
                        'enabled' => false,
                        'installed' => true,
                        'api' => null,
                    ];
                }
                ExtensionManager::writeExtensionJson($extensions);

                \Artisan::call('cache:clear');
                \Artisan::call('view:clear');
                \Artisan::call('config:clear');

                ActionLog::log(ActionLog::EXTENSION_UPDATED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type, 'source' => 'zip']);

                File::deleteDirectory($tmpRoot);

                return $this->respondWithSuccess("Extension importée avec succès.");
            } catch (\Throwable $e) {
                if (File::isDirectory($destination)) {
                    File::deleteDirectory($destination);
                }
                if ($backupPath && File::isDirectory($backupPath)) {
                    File::copyDirectory($backupPath, $destination);
                }
                File::deleteDirectory($tmpRoot);
                \Log::error('Extension ZIP import failed with rollback', ['error' => $e->getMessage()]);

                return $this->respondWithError("Import échoué, rollback appliqué automatiquement.");
            }
        } finally {
            optional($lock)->release();
        }
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
