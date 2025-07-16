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
namespace App\DTO\Core\Extensions;

use App\Core\License\LicenseCache;
use Illuminate\Contracts\Support\Arrayable;

class ExtensionDTO implements Arrayable
{
    const TYPE_ADDON = 'addon';

    const TYPE_THEME = 'theme';

    const TYPE_MODULE = 'module';

    const TYPE_COMPONENT = 'component';

    public string $uuid;

    public ?string $version = null;

    public string $type;

    public bool $installed;

    public bool $enabled;

    public array $api;

    public function __construct(string $uuid, string $type, bool $installed, bool $enabled, array $api = [], ?string $version = null)
    {
        $this->uuid = $uuid;
        $this->type = $type;
        $this->installed = $installed;
        $this->enabled = $enabled;
        $this->api = $api;
        $this->version = $version;
    }

    public static function fromArray(array $module)
    {
        return new self(
            $module['uuid'],
            $module['type'],
            $module['installed'],
            $module['enabled'],
            $module['api'] ?? [],
            $module['version'],
        );
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
        if (array_key_exists('unofficial', $this->api)) {
            return $this->api['thumbnail'];
        }
        if (array_key_exists('thumbnail', $this->api)) {
            return "https://api-nextgen.clientxcms.com/assets/{$this->api['thumbnail']}";
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

        if (! array_key_exists('translates', $this->api)) {
            return [
                'name' => $this->uuid,
                'description' => $this->uuid,
            ];
        }
        $get = collect($this->api['translates'])->first(fn ($translate) => $translate['locale'] === $locale);
        if ($get == null) {
            return [
                'name' => $this->uuid,
                'description' => $this->uuid,
            ];
        }

        return $get;
    }

    public function prices(): array
    {
        return $this->api['prices'] ?? [];
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

    private function isIncluded()
    {
        if (array_key_exists('unofficial', $this->api)) {
            return true;
        }
        if (! empty($this->prices()) && $this->prices()[0]['billing'] == 'included') {
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
