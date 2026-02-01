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

namespace App\Extensions;

use App\Core\License\LicenseGateway;
use App\DTO\Core\Extensions\ExtensionDTO;
use App\Exceptions\ExtensionException;
use App\Models\ActionLog;
use App\Models\Admin\Setting;
use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

class ExtensionManager extends ExtensionCollectionsManager
{
    private Filesystem $files;

    private array $extensions = [];

    private static ?array $testExtensions = null;

    public function __construct()
    {
        $this->files = new Filesystem;
        parent::__construct();
    }

    public function autoload(Application $app, bool $enabledOnly = true): void
    {
        $composer = $this->files->getRequire(base_path('vendor/autoload.php'));
        $this->autoloadModules($composer, $enabledOnly);
        $this->autoloadAddons($composer, $enabledOnly);
    }

    public static function readExtensionJson(): array
    {
        if (app()->environment('testing')) {
            if (self::$testExtensions === null) {
                self::$testExtensions = [];
            }

            return self::$testExtensions;
        }

        $path = base_path('bootstrap/cache/extensions.json');
        if (! file_exists($path)) {
            self::writeExtensionJson([]);
        }
        $json = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ExtensionException('Unable to read extensions.json file');
        }

        return $json;
    }

    /**
     * @throws \Exception
     */
    public static function writeExtensionJson(array $extensions, ?string $action = null, ?string $extensionUuid = null): void
    {
        if (app()->environment('testing')) {
            self::$testExtensions = $extensions;

            return;
        }

        $dir = base_path('bootstrap/cache');
        self::writeExtensionJsonToFile($extensions, $dir);
        self::logStateChange($extensions, $action, $extensionUuid);
    }

    /**
     * Write extensions array to a directory using atomic temp file + rename pattern.
     * Uses advisory file lock to prevent concurrent write corruption.
     * Extracted for testability with custom directories.
     */
    public static function writeExtensionJsonToFile(array $extensions, string $dir): void
    {
        $path = $dir.'/extensions.json';
        $tmpPath = $dir.'/extensions.json.tmp';
        $bakPath = $dir.'/extensions.json.bak';
        $lockPath = $dir.'/extensions.json.lock';

        try {
            if (! file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        } catch (\Exception $e) {
            throw new ExtensionException('Unable to create bootstrap/cache directory');
        }

        // Acquire advisory lock to prevent concurrent write corruption
        $lockFp = fopen($lockPath, 'c');
        if ($lockFp === false || ! flock($lockFp, LOCK_EX)) {
            throw new ExtensionException('Unable to acquire lock for extensions.json');
        }

        try {
            // Create backup of existing file
            if (file_exists($path)) {
                copy($path, $bakPath);
            }

            // Encode and validate JSON before writing
            $json = json_encode($extensions, JSON_PRETTY_PRINT);
            if ($json === false) {
                throw new ExtensionException('Unable to encode extensions to JSON: '.json_last_error_msg());
            }

            // Write to temp file, verify complete write
            $written = file_put_contents($tmpPath, $json);
            if ($written === false || $written !== strlen($json)) {
                throw new ExtensionException('Unable to write extensions.json temp file');
            }

            // Atomic rename
            if (! rename($tmpPath, $path)) {
                // Cleanup temp file on failure
                if (file_exists($tmpPath)) {
                    unlink($tmpPath);
                }

                throw new ExtensionException('Unable to write extensions.json file');
            }
        } finally {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
    }

    private static function logStateChange(array $extensions, ?string $action = null, ?string $extensionUuid = null): void
    {
        try {
            $staffId = auth('admin')->check() ? auth('admin')->id() : null;
            $enabled = collect($extensions['modules'] ?? [])
                ->merge($extensions['addons'] ?? [])
                ->merge($extensions['themes'] ?? [])
                ->merge($extensions['email_templates'] ?? [])
                ->where('enabled', true)
                ->pluck('uuid')
                ->toArray();

            ActionLog::log(
                ActionLog::SETTINGS_UPDATED,
                ExtensionDTO::class,
                $extensionUuid ?? 'extensions.json',
                $staffId,
                null,
                [
                    'action' => $action ?? 'state_change',
                    'enabled_extensions' => $enabled,
                ]
            );
        } catch (\Exception $e) {
            \Log::warning('Failed to log extension state change', [
                'action' => $action,
                'extension' => $extensionUuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function fetch()
    {
        $cache = new \Illuminate\Cache\CacheManager(app());
        if ($cache->has('extensions_array') && $this->extensions == []) {
            $this->extensions = $cache->get('extensions_array');
        }
        if ($this->extensions !== []) {
            return $this->extensions;
        }
        $extensions = self::makeRequest();
        $cache->put('extensions_array', $extensions, now()->addDays(7));

        return $extensions;
    }

    public function getGroupsWithExtensions(): array
    {
        $fetch = $this->fetch();
        $groups = $fetch['groups'] ?? [];
        $items = $this->getAllExtensions();
        $return = [];
        foreach ($groups as $group) {
            $return[$group['name']] = ['items' => collect($items)->filter(function (ExtensionDTO $item) use ($group) {
                if (! array_key_exists('group_uuid', $item->api)) {
                    return false;
                }

                return $item->api['group_uuid'] == $group['uuid'];
            }), 'icon' => $group['icon']];
        }
        $return['Un Official'] = ['items' => collect($items)->filter(function (ExtensionDTO $item) {
            return $item->isUnofficial();
        }), 'icon' => 'bi bi-star'];
        foreach ($return as $key => $group) {
            if ($group['items']->isEmpty()) {
                unset($return[$key]);
            }
        }

        return $return;
    }

    private static function makeRequest()
    {
        if (app()->environment('testing')) {
            return [];
        }
        try {
            $response = \Http::timeout(10)->get(LicenseGateway::getDomain().'/api/resources');

            return $response->json('data', []);
        } catch (\Exception $e) {
            throw new ExtensionException($e->getMessage());
        }
    }

    public function getExtension(string $type, string $uuid): ExtensionDTO
    {
        $extensions = $this->getAllExtensions();
        $extension = $extensions->first(function ($item) use ($uuid, $type) {
            return $item->uuid == $uuid && $item->type() == $type;
        });
        if ($extension == null) {
            throw new ExtensionException('Extension not found');
        }

        return $extension;
    }

    public function getAllExtensions(bool $withTheme = true, bool $withUnofficial = true)
    {
        $installed = $this->fetchInstalledExtensions();
        $installedVersions = collect($installed)->pluck('version', 'uuid')->toArray();
        $enabled = $this->fetchEnabledExtensions();
        $theme = app('theme')->getTheme();
        $enabled = array_merge($enabled, [$theme->uuid]);
        $installedVersions[$theme->uuid] = $theme->version;
        if (setting('email_template_name') != null) {
            $enabled = array_merge($enabled, [\setting('email_template_name')]);
        }
        $return = collect($this->fetch()['items'] ?? [])->filter(function (array $extensionDTO) use ($withTheme) {
            $allowedTypes = ['module', 'addon', 'email_template', 'invoice_template'];
            if ($withTheme) {
                $allowedTypes[] = 'theme';
            }

            return in_array($extensionDTO['type'], $allowedTypes);
        })->map(function ($extension) use ($installedVersions, $enabled) {
            $latestVersion = $extension['version'] ?? null;
            $extension['enabled'] = in_array($extension['uuid'], $enabled);
            $extension['api'] = $extension;
            $extension['api']['latest_version'] = $latestVersion;
            $extension['version'] = $installedVersions[$extension['uuid']] ?? null;

            return ExtensionDTO::fromArray($extension);
        });
        if (! $withUnofficial) {
            return $return;
        }
        $unofficial = $this->fetchUnofficialExtensions($return->pluck('uuid')->toArray(), $enabled);

        return $return->merge($unofficial);
    }

    public function fetchInstalledExtensions()
    {
        $extensions = self::readExtensionJson();

        return collect($extensions['modules'] ?? [])->merge($extensions['addons'] ?? [])->merge($extensions['themes'] ?? [])->merge($extensions['email_templates'] ?? [])->where('installed', true)->toArray();
    }

    public function extensionIsEnabled(string $uuid): bool
    {
        $extensions = $this->fetchEnabledExtensions();

        return in_array($uuid, $extensions);
    }

    public function getVersion(string $uuid): ?string
    {
        $extensions = $this->fetchInstalledExtensions();
        $extension = collect($extensions)->first(function ($item) use ($uuid) {
            return $item['uuid'] == $uuid;
        });

        return $extension['version'] ?? null;
    }

    public function fetchEnabledExtensions(): array
    {
        $extensions = self::readExtensionJson();
        $extensions = collect($extensions['modules'] ?? [])->merge($extensions['addons'] ?? [])->merge($extensions['themes'] ?? [])->merge($extensions['email_templates'] ?? [])->where('enabled', true);
        foreach ($extensions as $extension) {
            if (! ExtensionDTO::fromArray($extension)->isActivable()) {
                $extensions = $extensions->filter(function ($item) use ($extension) {
                    return $item['uuid'] != $extension['uuid'];
                });
            }
        }

        return $extensions->pluck('uuid')->toArray();
    }

    public function update(string $type, string $extension)
    {
        $extensions = self::readExtensionJson();
        $api = collect($this->fetch()['items'] ?? [])->first(function ($item) use ($extension) {
            return $item['uuid'] == $extension;
        });
        if ($api == null) {
            throw new ExtensionException('Extension not found in the API');
        }
        $extensions[$type] = collect($extensions[$type] ?? [])->map(function ($item) use ($extension, $api) {
            if ($item['uuid'] == $extension) {
                $item['version'] = $api['version'];
                $item['api'] = $api;
            }

            return $item;
        })->toArray();

        try {
            (new UpdaterManager)->update($api['uuid']);
            self::writeExtensionJson($extensions, 'update', $extension);
        } catch (\Exception $e) {
            throw new ExtensionException('Error in UpdaterManager: '.$e->getMessage());
        }
    }

    public function checkPrerequisitesForEnable(string $type, string $extension): array
    {
        if ($type == 'themes') {
            $file = base_path('resources/themes/'.$extension.'/theme.json');
        } elseif ($type == 'addons' || $type == 'modules') {
            $file = base_path($type.'/'.$extension.'/composer.json');
        } else {
            return [];
        }
        if (! file_exists($file)) {
            throw new ExtensionException(__('extensions.flash.composer_not_found'));
        }
        if ($type == 'themes') {
            return [];
        }
        $composerJson = json_decode((new Filesystem)->get($file), true);

        return $this->checkPrerequisites($composerJson);
    }

    public function enable(string $type, string $extension)
    {
        $extensions = self::readExtensionJson();
        $api = collect($this->fetch()['items'] ?? [])->first(function ($item) use ($extension) {
            return $item['uuid'] == $extension;
        });
        if ($api == null) {
            $AllExtensions = $this->getAllExtensions();
            $api = $AllExtensions->first(function ($item) use ($extension) {
                return $item->uuid == $extension;
            });
            if ($api == null) {
                throw new ExtensionException('Extension not found');
            }
            $api = $api->api;
        }
        if ($type == 'email_templates') {
            Setting::updateSettings(['email_template_name' => $extension]);
        }
        if ($type == 'themes') {
            app('theme')->setTheme($extension, true);
        }
        if (collect($extensions[$type] ?? [])->where('uuid', $extension)->isEmpty()) {
            $extensions[$type][] = ['uuid' => $extension, 'version' => $api['version'] ?? 'v1.0', 'type' => $type, 'enabled' => true, 'installed' => true, 'api' => $api];
        }
        $extensions[$type] = collect($extensions[$type])->map(function ($item) use ($extension, $api, $type) {
            if ($item['uuid'] == $extension) {
                $item['enabled'] = true;
                $item['api'] = $api;
            } elseif ($type == 'themes') {
                // Themes are mutually exclusive: only one active at a time
                $item['enabled'] = false;
            }

            return $item;
        })->toArray();
        try {
            self::writeExtensionJson($extensions, 'enable', $extension);
        } catch (\Exception $e) {
            throw new ExtensionException('Unable to write extensions.json file: '.$e->getMessage());
        }
    }

    public function uninstall(string $type, string $uuid): void
    {
        $extensions = self::readExtensionJson();

        $entry = collect($extensions[$type] ?? [])->first(fn ($item) => $item['uuid'] === $uuid);

        if (! $entry) {
            throw new ExtensionException('Extension not found in registry');
        }

        if (! empty($entry['enabled'])) {
            throw new ExtensionException(__('extensions.flash.must_disable_before_uninstall'));
        }

        if (! empty($entry['api']['unofficial'])) {
            throw new ExtensionException(__('extensions.flash.cannot_uninstall_unofficial'));
        }

        $dto = ExtensionDTO::fromArray(array_merge($entry, ['type' => $this->singularType($type)]));
        $path = $dto->extensionPath();

        if ($this->files->isDirectory($path)) {
            $this->files->deleteDirectory($path);
        } elseif ($this->files->exists($path)) {
            $this->files->delete($path);
        }

        $extensions[$type] = collect($extensions[$type] ?? [])
            ->reject(fn ($item) => $item['uuid'] === $uuid)
            ->values()
            ->toArray();

        self::writeExtensionJson($extensions, 'uninstall', $uuid);
    }

    private function singularType(string $type): string
    {
        $map = [
            'modules' => 'module',
            'addons' => 'addon',
            'themes' => 'theme',
            'email_templates' => 'email_template',
            'invoice_templates' => 'invoice_template',
        ];

        return $map[$type] ?? rtrim($type, 's');
    }

    public function disable(string $type, string $extension)
    {
        $extensions = self::readExtensionJson();
        $extensions[$type] = collect($extensions[$type] ?? [])->map(function ($item) use ($extension) {
            if ($item['uuid'] == $extension) {
                $item['enabled'] = false;
            }

            return $item;
        })->toArray();
        if ($type == 'email_templates') {
            Setting::updateSettings(['email_template_name' => null]);
        }
        try {
            self::writeExtensionJson($extensions, 'disable', $extension);
        } catch (\Exception $e) {
            \Log::error('Failed to disable extension', [
                'extension' => $extension,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            throw new ExtensionException('Unable to disable extension: '.$e->getMessage());
        }
    }

    public function checkPrerequisites(array $composerJson): array
    {
        $errors = [];
        $parser = new VersionParser;
        $prerequisites = $composerJson['prerequisites'] ?? [];
        foreach ($prerequisites as $prerequisite => $version) {
            if ($version == 'loaded') {
                if (! extension_loaded($prerequisite)) {
                    $errors[] = __('extensions.flash.extension_not_loaded', ['extension' => $prerequisite]);
                }
            } else {
                $currentVersion = $this->getVersion($prerequisite);
                if ($currentVersion == null || ! $this->extensionIsEnabled($prerequisite)) {
                    $errors[] = __('extensions.flash.extension_not_enabled', ['extension' => $prerequisite]);

                    continue;
                }
                $min = $parser->parseConstraints($version);
                $current = $parser->parseConstraints($currentVersion);
                if (! $min->matches($current)) {
                    $errors[] = __('extensions.flash.extension_version_not_compatible', ['extension' => $prerequisite, 'version' => $version, 'current' => $currentVersion]);
                }
            }
        }

        return $errors;
    }

    private function autoloadModules(ClassLoader $composer, bool $enabledOnly = true)
    {
        $modules = app('module')->getExtensions($enabledOnly);
        foreach ($modules as $module) {
            app('module')->autoload($module, app(), $composer);
        }
    }

    private function autoloadAddons(ClassLoader $composer, bool $enabledOnly = true)
    {
        $addons = app('addon')->getExtensions($enabledOnly);
        foreach ($addons as $addon) {
            app('addon')->autoload($addon, app(), $composer);
        }
    }

    private function fetchUnofficialExtensions(array $extensions, array $enabled)
    {
        $unofficial = [];
        $unofficial = array_merge($unofficial, $this->scanFolder('modules', 'module', $extensions, $enabled));
        $unofficial = array_merge($unofficial, $this->scanFolder('resources/themes', 'theme', $extensions, $enabled));

        return array_merge($unofficial, $this->scanFolder('addons', 'addon', $extensions, $enabled));
    }

    private function scanFolder(string $folder, string $type, array $extensions, array $enabled)
    {
        $scan = $this->files->directories(base_path($folder));
        $unofficial = [];
        foreach ($scan as $extension) {
            $pathinfo = pathinfo($extension);
            if (in_array($pathinfo['basename'], $extensions)) {
                continue;
            }
            $extensionFile = $extension.'/'.$type.'.json';
            if (! file_exists($extensionFile)) {
                continue;
            }
            $extension = json_decode(file_get_contents($extensionFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }
            $unofficial[] = ExtensionDTO::fromArray([
                'uuid' => $extension['uuid'],
                'version' => $extension['version'] ?? '1.0.0',
                'type' => $type,
                'installed' => true,
                'enabled' => in_array($extension['uuid'], $enabled),
                'api' => [
                    'name' => $extension['name'],
                    'description' => $extension['description'] ?? null,
                    'unofficial' => true,
                    'prices' => $extension['prices'] ?? [],
                    'thumbnail' => $extension['thumbnail'] ?? null,
                    'author' => $extension['author'] ?? ['name' => 'Unknown'],
                    'providers' => collect($extension['providers'] ?? [])->map(function ($provider) {
                        return [
                            'provider' => $provider,
                        ];
                    })->toArray(),
                ],
            ]);
        }

        return $unofficial;
    }
}
