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
namespace App\Extensions;

use App\DTO\Core\Extensions\ExtensionDTO;
use App\Exceptions\ExtensionException;
use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

class ExtensionManager extends ExtensionCollectionsManager
{
    private Filesystem $files;

    private array $extensions = [];

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
        // $this->autoloadComponants($composer);
    }

    public static function readExtensionJson(): array
    {
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
    public static function writeExtensionJson(array $extensions): void
    {
        try {
            $path = base_path('bootstrap/cache');
            if (! file_exists($path)) {
                mkdir($path, 0777, true);
            }
        } catch (\Exception $e) {
            throw new ExtensionException('Unable to create bootstrap/cache directory');
        }
        $path = base_path('bootstrap/cache/extensions.json');
        $edit = file_put_contents($path, json_encode($extensions, JSON_PRETTY_PRINT));
        if (! $edit) {
            throw new ExtensionException('Unable to write extensions.json file');
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
        $extensions = [];
        $extensions['modules'] = self::makeRequest('modules');
        $extensions['addons'] = self::makeRequest('addons');
        $extensions['themes'] = self::makeRequest('themes');
        $extensions['components'] = self::makeRequest('components');
        $extensions['extensions_types'] = [];
        foreach ($extensions as $type => $extension) {
            if (! is_array($extension)) {
                continue;
            }
            foreach ($extension as $_extension) {
                $extensions['extensions_types'][$_extension['uuid']] = $type;
            }
        }
        $cache->put('extensions_array', $extensions, now()->addDays(7));

        return $extensions;
    }

    private static function makeRequest(string $endpoint)
    {
        try {
            $response = \Http::timeout(10)->get('https://api-nextgen.clientxcms.com/items/'.$endpoint.'?filter[status]=published');

            return $response->json('data');
        } catch (\Exception $e) {
            throw new ExtensionException($e->getMessage());
        }
    }

    public function getExtension(string $type, string $uuid): ExtensionDTO
    {
        $extensions = $this->getAllExtensions();
        $extension = $extensions->first(function ($item) use ($uuid, $type) {
            return $item->uuid == $uuid && $item->type == $type;
        });
        if ($extension == null) {
            throw new ExtensionException('Extension not found');
        }

        return $extension;
    }

    public function getAllExtensions(bool $withTheme = true, bool $withUnofficial = true)
    {
        $installed = $this->fetchInstalledExtensions();
        $versions = collect($installed)->pluck('version')->toArray();
        $uuids = collect($installed)->pluck('uuid')->toArray();
        $enabled = $this->fetchEnabledExtensions();
        $fetch = $this->fetch();
        $extensions = array_merge($fetch['modules'] ?? [], $fetch['addons'] ?? [], $withTheme ? $fetch['themes'] ?? [] : [], $fetch['components'] ?? []);
        $return = collect($extensions)->map(function ($extension) use ($uuids, $enabled, $versions, $fetch) {
            $extension['api'] = $extension;
            $extension['installed'] = in_array($extension['uuid'], $uuids);
            $extension['version'] = $versions[array_search($extension['uuid'], $uuids)] ?? null;
            $extension['type'] = $fetch['extensions_types'][$extension['uuid']] ?? 'module';
            $extension['enabled'] = in_array($extension['uuid'], $enabled);
            $extension['uuid'] = $extension['uuid'] ?? $extension['id'];

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

        return collect($extensions['modules'] ?? [])->merge($extensions['addons'] ?? [])->merge($extensions['themes'] ?? [])->merge($extensions['components'] ?? [])->where('installed', true)->toArray();
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

    public function canBeActivated(string $uuid): bool
    {
        return true;
    }

    public function fetchEnabledExtensions(): array
    {
        $extensions = self::readExtensionJson();
        $extensions = collect($extensions['modules'] ?? [])->merge($extensions['addons'] ?? [])->merge($extensions['themes'] ?? [])->merge($extensions['components'] ?? [])->where('enabled', true);
        foreach ($extensions as $extension) {
            if (! ExtensionDTO::fromArray($extension)->isActivable()) {
                $extensions = $extensions->filter(function ($item) use ($extension) {
                    return $item['uuid'] != $extension['uuid'];
                });
            }

        }

        return $extensions->pluck('uuid')->toArray();
    }

    public function enable(string $type, string $extension)
    {
        $extensions = self::readExtensionJson();
        $api = collect($this->fetch()[$type])->first(function ($item) use ($extension) {
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
        if (collect($extensions[$type] ?? [])->where('uuid', $extension)->isEmpty()) {
            $extensions[$type][] = ['uuid' => $extension, 'version' => 'v1.0', 'type' => $type, 'enabled' => true, 'installed' => true, 'api' => $api];
        }
        $extensions[$type] = collect($extensions[$type])->map(function ($item) use ($extension, $api) {
            if ($item['uuid'] == $extension) {
                $item['enabled'] = true;
                $item['api'] = $api;
            }

            return $item;
        })->toArray();
        try {
            self::writeExtensionJson($extensions);
        } catch (\Exception $e) {
        }
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
        try {
            self::writeExtensionJson($extensions);
        } catch (\Exception $e) {
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
                'version' => $extension['version'] ?? 'v1.0',
                'type' => $type.'s',
                'installed' => true,
                'enabled' => in_array($extension['uuid'], $enabled),
                'api' => [
                    'name' => $extension['name'],
                    'description' => $extension['description'] ?? null,
                    'unofficial' => true,
                    'prices' => $extension['prices'] ?? [],
                    'thumbnail' => $extension['thumbnail'] ?? null,
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
