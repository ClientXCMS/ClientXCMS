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

namespace App\DTO\Core\Extensions;

use App\Core\License\LicenseCache;
use Illuminate\Contracts\Support\Arrayable;

class ExtensionDTO implements Arrayable
{
    public string $uuid;

    public ?string $version = null;

    public string $type;

    public bool $installed;

    public bool $enabled;

    public array $api;

    public function __construct(string $uuid, string $type, bool $enabled, array $api = [], ?string $version = null)
    {
        $this->uuid = $uuid;
        $this->type = $type;
        $this->installed = file_exists($this->extensionPath());
        $this->enabled = $enabled;
        $this->api = $api;
        $this->version = $version;
    }

    public function extensionPath(): string
    {
        if ($this->type == 'theme') {
            return base_path('resources/themes/'.$this->uuid);
        }
        if ($this->type == 'email_template' || $this->type == 'invoice_template') {
            return base_path('resources/views/vendor/notifications/'.$this->uuid.'.blade.php');
        }

        return base_path($this->type().'/'.$this->uuid);
    }

    public static function fromArray(array $module)
    {
        return new self(
            $module['uuid'],
            $module['type'],
            $module['enabled'],
            $module['api'] ?? [],
            $module['version'],
        );
    }

    public function supportMigration(): bool
    {
        return in_array($this->type, ['addon', 'theme', 'module']);
    }

    public function author()
    {
        if (array_key_exists('author', $this->api) && is_array($this->api['author'])) {
            return $this->api['author']['name'] ?? 'Unknown';
        }
        if (array_key_exists('author', $this->api) && is_string($this->api['author'])) {
            return $this->api['author'];
        }

        return 'Unknown';
    }

    public function hasPadding()
    {
        return $this->type !== 'theme';
    }

    public function toArray()
    {
        return [
            'uuid' => $this->uuid,
            'version' => $this->version,
            'type' => $this->type,
            'installed' => $this->installed,
            'enabled' => $this->enabled,
            'api' => $this->api,
        ];
    }

    public function isUnofficial()
    {
        return array_key_exists('unofficial', $this->api) && $this->api['unofficial'] === true;
    }

    public function getLatestVersion(): ?string
    {
        if (array_key_exists('unofficial', $this->api)) {
            return $this->version;
        }
        if (array_key_exists('latest_version', $this->api)) {
            return $this->api['latest_version'];
        }
        if (array_key_exists('version', $this->api)) {
            return $this->api['version'];
        }

        return null;
    }

    public function type()
    {
        return $this->type.'s';
    }

    public function name()
    {
        return $this->getTranslates()['name'];
    }

    public function isNotInstalled()
    {
        return ! $this->installed;
    }

    public function isInstalled()
    {
        return $this->installed;
    }

    public function isNotEnabled()
    {
        return ! $this->isEnabled();
    }

    public function isEnabled()
    {
        if ($this->isActivable()) {
            return $this->enabled;
        }

        return false;
    }

    public function thumbnail()
    {
        if ($this->type == 'theme' && file_exists(base_path('resources/themes/'.$this->uuid.'/screenshot.png'))) {
            try {
                return \Vite::asset('resources/themes/'.$this->uuid.'/screenshot.png');
            } catch (\Exception $e) {
                \Log::warning('Failed to resolve Vite asset for theme thumbnail', [
                    'theme' => $this->uuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        if (array_key_exists('unofficial', $this->api) || ! empty($this->api['thumbnail'])) {
            return $this->api['thumbnail'];
        }

        return 'https://via.placeholder.com/150';
    }

    public function description()
    {
        return $this->getTranslates()['description'];
    }

    private function getTranslates()
    {
        if (array_key_exists('unofficial', $this->api)) {
            return [
                'name' => $this->api['name'],
                'description' => $this->api['description'],
            ];
        }
        $locale = app()->getLocale();
        if (! array_key_exists('translations', $this->api)) {
            return [
                'name' => $this->uuid,
                'description' => $this->uuid,
            ];
        }
        $translations = $this->api['translations'];

        return [
            'name' => $translations['name'][$locale] ?? ($this->api['name'] ?? $this->uuid),
            'description' => $translations['short_description'][$locale] ?? ($this->api['short_description'] ?? $this->uuid),
        ];
    }

    public function price(bool $formatted = true)
    {
        $key = $formatted ? 'formatted_price' : 'price';

        return $this->api[$key] ?? ($formatted ? __('global.free') : 0);
    }

    public function isActivable(): bool
    {
        if ($this->isIncluded()) {
            return true;
        }
        $extensions = LicenseCache::get()?->getExtensionsUuids();
        if ($extensions == null) {
            return false;
        }
        if (is_array($extensions) && in_array($this->uuid, $extensions)) {
            return true;
        }

        return false;
    }

    public function canBeEnabled(): bool
    {
        return in_array($this->type, ['addon', 'module', 'theme']);
    }

    private function isIncluded()
    {
        if (array_key_exists('unofficial', $this->api)) {
            return true;
        }
        if ($this->price(false) == 0) {
            return true;
        }

        return false;
    }

    public function getSections()
    {
        $file = base_path($this->type.'/'.$this->uuid.'/views/default/sections');
        if (! \File::exists($file)) {
            return [];
        }
        $sectionFile = [];
        if (file_exists(base_path($this->type.'/'.$this->uuid.'/views/default/sections/sections.json'))) {
            $sectionFile = json_decode(file_get_contents(base_path($this->type.'/'.$this->uuid.'/views/default/sections/sections.json')), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $sectionFile = [];
            }
        }
        $sections = [];
        foreach ($sectionFile as $section) {
            $sections[] = new ThemeSectionDTO($section);
        }

        return $sections;
    }
}
