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

use App\Exceptions\ThemeInvalidException;
use App\Models\Admin\Setting;
use File;
use Illuminate\Validation\ValidationException;
use Validator;
use Vite;

class ExtensionThemeDTO
{
    public string $path;

    public string $theme_file;

    public bool $enabled = false;

    public array $json = [];

    public string $uuid;

    public string $name;

    public string $description;

    public string $version;

    public array $author;

    public ?string $demo = null;

    public array $api;

    public bool $hasConfig;

    public ?string $configFile = null;

    public array $configRules = [];

    public ?string $configRulesFile = null;

    public array $config = [];

    public array $dbSettings = [];

    public ?string $dbSettingsFile = null;

    public static function fromJson(string $theme_file)
    {
        $json = json_decode(File::get($theme_file), true);
        if ($json === null) {
            throw new ThemeInvalidException("Invalid JSON in theme file: {$theme_file}");
        }
        $dto = new self;
        if ($error = $dto->verifyJson($json)) {
            throw new ThemeInvalidException("Invalid JSON in theme file: {$theme_file} : {$error}");
        }
        $dto->theme_file = $theme_file;
        $dto->json = $json;
        $dto->api = $json;
        $dto->path = dirname($theme_file);
        $dto->uuid = $json['uuid'];
        $dto->name = $json['name'];
        $dto->description = $json['description'];
        $dto->version = $json['version'];
        $dto->author = $json['author'];
        $dto->demo = $json['demo'] ?? null;
        $dto->hasConfig = file_exists($dto->path.'/config/config.blade.php');
        if ($dto->hasConfig) {
            $dto->configFile = $dto->path.'/config/config.php';
            if (file_exists($dto->path.'/config/rules.php')) {
                $dto->configRulesFile = $dto->path.'/config/rules.php';
            }
            if (file_exists($dto->path.'/config/config.json')) {
                $dto->config = json_decode(file_get_contents($dto->path.'/config/config.json'), true);
            }
        }

        // Load DB settings configuration (keys to store in database for translation)
        $dbSettingsPath = $dto->path . '/config/db_settings.php';
        if (file_exists($dbSettingsPath)) {
            $dto->dbSettingsFile = $dbSettingsPath;
            $dto->dbSettings = require $dbSettingsPath;
        }

        return $dto;
    }

    private function verifyJson(array $json)
    {
        $required = ['uuid', 'name', 'description', 'version', 'author'];
        foreach ($required as $key) {
            if (! isset($json[$key])) {
                return "Missing required key: {$key}";
            }
            if (! is_string($json[$key]) && ! is_array($json[$key])) {
                return "{$key} must be a string";
            }
        }
        if (! is_array($json['author'])) {
            return 'author must be an array';
        }
        if (! isset($json['author']['name']) || ! is_string($json['author']['name'])) {
            return 'author.name must be a string';
        }
        if (! isset($json['author']['email']) || ! is_string($json['author']['email'])) {
            return 'author.email must be a string';
        }
    }

    public static function fromApi(array $theme)
    {
        $dto = new self;
        $dto->json = $theme;
        $dto->api = $theme;
        $dto->path = dirname('resources/themes/'.$theme['uuid']);
        $dto->uuid = $theme['uuid'];
        $dto->name = $dto->getTranslates()['name'];
        $dto->description = $dto->getTranslates()['description'];
        $dto->version = $theme['version'] ?? 'v1.0';
        $dto->author = $theme['author'];
        $dto->demo = $theme['demonstration'] ?? null;
        $dto->hasConfig = false;

        return $dto;
    }

    public function isOfficial(): bool
    {
        return $this->api != null;
    }

    public function isEnabled(): bool
    {
        return app('settings')->get('theme.'.$this->uuid.'.enabled', false);
    }

    public function demoUrl(): string
    {
        return $this->demo ?? 'https://demo.clientxcms.com';
    }

    public function hasConfig(): bool
    {
        return $this->hasConfig;
    }

    public function configRules(): array
    {
        if ($this->configRulesFile && empty($this->configRules)) {
            $this->configRules = require $this->configRulesFile;
        }

        return $this->configRules;
    }

    public function configView(array $params): string
    {
        if (! $this->hasConfig) {
            return '';
        }

        return view()->file($this->path.'/config/config.blade.php', array_merge($params, ['config' => $this->config]))->render();
    }

    public function storeConfig(array $data): void
    {
        $rules = $this->configRules();
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $this->config = $validated;

        $dbSettingsData = [];
        $fileSettings = $validated;

        if (!empty($this->dbSettings)) {
            foreach ($this->dbSettings as $key) {
                if (array_key_exists($key, $validated)) {
                    $dbSettingsData[$key] = $validated[$key];
                    unset($fileSettings[$key]);
                }
            }
        }

        if (!empty($dbSettingsData)) {
            Setting::updateSettings($dbSettingsData);
        }

        if ($this->configRulesFile !== null) {
            file_put_contents(
                $this->path . '/config/config.json',
                json_encode($fileSettings, JSON_PRETTY_PRINT)
            );
        }
    }

    public function hasScreenshot(): bool
    {
        if ($this->api['thumbnail'] ?? false) {
            return true;
        }

        return file_exists($this->path.'/screenshot.png');
    }

    public function screenshotUrl(): string
    {
        if ($this->api['thumbnail'] ?? false) {
            return $this->api['thumbnail'];
        }

        return Vite::asset('resources/themes/'.$this->uuid.'/screenshot.png');
    }

    public function hasSection(string $path): bool
    {
        return file_exists($this->path.'/sections/'.$path.'.blade.php');
    }

    public function hasSections(): bool
    {
        return file_exists($this->path.'/views/sections');
    }

    public function scanSections(): array
    {
        $sections = [];
        if ($this->hasSections()) {
            $files = File::allFiles($this->path.'/views/sections');
            foreach ($files as $file) {
                if (! str_contains($file->getFilename(), '_copy')) {
                    $path = str_replace($this->path.'/views/', '', $file->getPathname());
                    $sections[] = ThemeSectionDTO::fromPathInfo(pathinfo($file), $path, $this->uuid);
                }
            }
        }

        return $sections;
    }

    public function getSections()
    {
        $file = $this->path.'/views/sections/sections.json';
        if (! \File::exists($file)) {
            return [];
        }
        $sectionFile = json_decode(file_get_contents($file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $sectionFile = [];
        }
        $sections = [];
        foreach ($sectionFile as $section) {
            $sections[] = new ThemeSectionDTO($section);
        }

        return $sections;
    }

    public function supportOption(string $key)
    {
        return $this->json['supported_options'][$key] ?? false;
    }

    private function getTranslates()
    {
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

    /**
     * Get the parent theme of the theme (default or bootstrap)
     */
    public function getParentTheme(): ?string
    {
        return $this->json['parent_theme'] ?? 'default';
    }

    public function toDto(): ExtensionDTO
    {
        return new ExtensionDTO($this->uuid, 'themes', $this->enabled, $this->api);
    }
}
