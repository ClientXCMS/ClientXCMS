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

class SettingsExtensionController
{
    private const ALLOWED_TYPES = ['modules', 'addons', 'themes', 'email_templates', 'invoice_templates'];

    public function showExtensions()
    {
        $apiDegraded = false;
        $groups = [];

        try {
            $groups = app('extension')->getGroupsWithExtensions();
        } catch (\Exception $e) {
            $apiDegraded = true;

            // AC2: Installed extensions must remain accessible from local data
            try {
                $raw = ExtensionManager::readExtensionJson();
                $installed = collect();
                $typeMap = [
                    'modules' => 'module',
                    'addons' => 'addon',
                    'themes' => 'theme',
                    'email_templates' => 'email_template',
                    'invoice_templates' => 'invoice_template',
                ];
                foreach ($typeMap as $plural => $singular) {
                    foreach ($raw[$plural] ?? [] as $ext) {
                        if (! empty($ext['installed'])) {
                            $ext['type'] = $singular;
                            $installed->push(ExtensionDTO::fromArray($ext));
                        }
                    }
                }

                if ($installed->isNotEmpty()) {
                    $groups = [
                        __('extensions.settings.sections.my_modules') => [
                            'items' => $installed,
                            'icon' => 'bi bi-collection-fill',
                        ],
                    ];
                }
            } catch (\Exception) {
                $groups = [];
            }
        }

        $card = app('settings')->getCards()->firstWhere('uuid', 'extensions');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'extensions');
        \View::share('current_card', $card);
        \View::share('current_item', $item);

        return view('admin.settings.extensions.index', [
            'groups' => $groups,
            'tags' => $apiDegraded ? [] : (app('extension')->fetch()['tags'] ?? []),
            'apiDegraded' => $apiDegraded,
        ]);
    }

    public function enable(Request $request, string $type, string $extension)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);

        if (! in_array($type, self::ALLOWED_TYPES)) {
            abort(404);
        }
        if (app('extension')->extensionIsEnabled($extension)) {
            return $this->respond($request, false, __('extensions.flash.already_enabled'), 'ENABLE_FAILED');
        }
        try {
            $extensiondto = app('extension')->getExtension($type, $extension);
        } catch (\Exception $e) {
            return $this->respond($request, false, $e->getMessage(), 'ENABLE_FAILED');
        }
        $prerequisites = app('extension')->checkPrerequisitesForEnable($type, $extension);
        if (! empty($prerequisites)) {
            return $this->respond($request, false, implode(', ', $prerequisites), 'ENABLE_FAILED');
        }
        try {
            if (! $extensiondto->isActivable()) {
                return $this->respond(
                    $request,
                    false,
                    __('extensions.flash.cannot_enable'),
                    'LICENSE_REQUIRED',
                    $extensiondto,
                    403,
                    ['purchase_url' => $extensiondto->api['route'] ?? null]
                );
            }
            app('extension')->enable($type, $extension);
        } catch (\Exception $e) {
            return $this->respond($request, false, $e->getMessage(), 'ENABLE_FAILED');
        }
        $this->postInstallCleanup($type, $extension);
        if ($type === 'themes') {
            ActionLog::log(ActionLog::THEME_CHANGED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type]);
        } else {
            ActionLog::log(ActionLog::EXTENSION_ENABLED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type]);
        }

        return $this->respond($request, true, __('extensions.flash.enabled'), null, $extensiondto);
    }

    public function disable(Request $request, string $type, string $extension)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);
        if (! in_array($type, self::ALLOWED_TYPES)) {
            abort(404);
        }

        // Active theme cannot be disabled: another theme must be activated first
        if ($type === 'themes' && app('theme')->getTheme()->uuid === $extension) {
            return $this->respond($request, false, __('extensions.flash.cannot_disable_active_theme'), 'DISABLE_BLOCKED');
        }

        try {
            app('extension')->disable($type, $extension);
        } catch (\Exception $e) {
            \Log::error('Extension disable failed', [
                'extension' => $extension,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return $this->respond($request, false, __('extensions.flash.disable_failed'), 'DISABLE_FAILED');
        }
        ActionLog::log(ActionLog::EXTENSION_DISABLED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type]);

        return $this->respond($request, true, __('extensions.flash.disabled'));
    }

    public function uninstall(Request $request, string $type, string $extension)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);

        if (! in_array($type, self::ALLOWED_TYPES)) {
            abort(404);
        }

        try {
            app('extension')->uninstall($type, $extension);
        } catch (\Exception $e) {
            return $this->respond($request, false, $e->getMessage(), 'UNINSTALL_FAILED');
        }

        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
        \Artisan::call('config:clear');

        ActionLog::log(
            ActionLog::EXTENSION_UNINSTALLED,
            ExtensionDTO::class,
            $extension,
            auth('admin')->id(),
            null,
            ['type' => $type]
        );

        return $this->respond($request, true, __('extensions.flash.uninstalled'));
    }

    public function clear(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);
        \Artisan::call('cache:clear');

        return $this->respond($request, true, __('extensions.flash.cache_cleared'));
    }

    public function clearCache(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
        \Artisan::call('config:clear');

        return $this->respond($request, true, __('extensions.flash.cache_cleared'));
    }

    public function update(Request $request, string $type, string $extension)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);
        if (! in_array($type, self::ALLOWED_TYPES)) {
            abort(404);
        }
        try {
            app('extension')->update($type, $extension);
        } catch (\Exception $e) {
            return $this->respond($request, false, $e->getMessage(), 'UPDATE_FAILED');
        }
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
        \Artisan::call('config:clear');

        ActionLog::log(ActionLog::EXTENSION_UPDATED, ExtensionDTO::class, $extension, auth('admin')->id(), null, ['type' => $type]);

        return $this->respond($request, true, __('extensions.flash.updated'));
    }

    public function install(Request $request, string $type, string $uuid)
    {
        staff_aborts_permission(Permission::MANAGE_EXTENSIONS);
        if (! in_array($type, self::ALLOWED_TYPES)) {
            abort(404);
        }

        // Step 1: Download extension files
        try {
            app('extension')->update($type, $uuid);
        } catch (\Exception $e) {
            return $this->respond($request, false, $e->getMessage(), 'DOWNLOAD_FAILED');
        }

        $activate = $request->boolean('activate', true);
        $extensiondto = null;

        // Step 2: Enable extension (only when activate=true, default behavior)
        if ($activate) {
            try {
                $extensiondto = app('extension')->getExtension($type, $uuid);
            } catch (\Exception $e) {
                return $this->respond($request, false, $e->getMessage(), 'DOWNLOAD_FAILED');
            }
            $prerequisites = app('extension')->checkPrerequisitesForEnable($type, $uuid);
            if (! empty($prerequisites)) {
                return $this->respond($request, false, implode(', ', $prerequisites), 'ENABLE_FAILED');
            }
            if (! $extensiondto->isActivable()) {
                return $this->respond(
                    $request,
                    false,
                    __('extensions.flash.cannot_enable'),
                    'LICENSE_REQUIRED',
                    $extensiondto,
                    403,
                    ['purchase_url' => $extensiondto->api['route'] ?? null]
                );
            }
            try {
                app('extension')->enable($type, $uuid);
            } catch (\Exception $e) {
                return $this->respond($request, false, $e->getMessage(), 'ENABLE_FAILED');
            }
        }

        // Step 3: Post-install cleanup (migrations, seeds, cache) runs regardless of activate
        $this->postInstallCleanup($type, $uuid);
        ActionLog::log(ActionLog::EXTENSION_INSTALLED, ExtensionDTO::class, $uuid, auth('admin')->id(), null, ['type' => $type]);

        // Get fresh DTO for response if not loaded during activation
        if (! $extensiondto) {
            try {
                $extensiondto = app('extension')->getExtension($type, $uuid);
            } catch (\Exception) {
                // DTO may not be resolvable after install-only
            }
        }

        return $this->respond($request, true, __('extensions.flash.installed'), null, $extensiondto);
    }

    private function postInstallCleanup(string $type, string $identifier): void
    {
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
        \Artisan::call('config:clear');
        \Artisan::call('db:seed', ['--force' => true]);
        if ($type === 'themes') {
            \App\Theme\ThemeManager::clearCache();
        } else {
            \Artisan::call('migrate', ['--force' => true, '--path' => $type.'/'.$identifier.'/database/migrations']);
        }
    }

    private function respond(
        Request $request,
        bool $success,
        string $message,
        ?string $errorCode = null,
        ?ExtensionDTO $extension = null,
        int $failStatusCode = 422,
        array $extraData = []
    ) {
        if ($request->expectsJson()) {
            $data = array_merge(
                ['extension' => $extension?->toArray()],
                $extraData
            );

            $response = [
                'success' => $success,
                'message' => $message,
                'data' => $data,
                'errors' => $errorCode ? [['code' => $errorCode, 'detail' => $message]] : [],
            ];

            return response()->json($response, $success ? 200 : $failStatusCode);
        }

        $flashKey = $success ? 'success' : 'error';

        return redirect()->back()->with($flashKey, $message);
    }
}
