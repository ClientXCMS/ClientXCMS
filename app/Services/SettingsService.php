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

namespace App\Services;

use App\DTO\Admin\Settings\SettingsCardDTO;
use App\DTO\Admin\Settings\SettingsItemDTO;
use App\Models\Admin\Setting;
use App\Models\Personalization\Translation;
use Illuminate\Support\Collection;

class SettingsService
{
    protected Collection $settings;

    protected Collection $cards;

    public Collection $savedSettings;

    private Collection $translatedSettings;

    public function __construct(?Collection $settings = null, ?Collection $cards = null)
    {
        $this->cards = $cards ?? collect();
        $this->settings = $settings ?? collect();
        $this->savedSettings = collect();
    }

    public function has(string $key): bool
    {
        return $this->settings->has($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings->get($key, $default);
    }

    public function setDefaultValue(string $key, mixed $value): void
    {
        if (! $this->has($key)) {
            $this->set($key, $value);
        }
    }

    public function set(array|string $key, mixed $value = null): void
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $name => $val) {
            if ($val == 'true') {
                $val = true;
            } elseif ($val == 'false') {
                $val = false;
            } elseif (is_numeric($val)) {
                $val = (int) $val;
            }
            $this->settings->put($name, $val);
        }
    }

    public function save(): void
    {
        Setting::updateSettings($this->settings->all());
    }

    public function addCard(string $uuid, string $name, string $description, int $order, ?Collection $items = null, bool $is_active = true, int $columns = 2, string $icon = 'bi bi-gear'): void
    {
        $this->cards->push(new SettingsCardDTO($uuid, $name, $description, $order, $items ?? collect(), $is_active, $columns, $icon));
    }

    public function getCards(): Collection
    {
        return collect($this->cards)->sort(fn ($a, $b) => $a->order <=> $b->order);
    }

    public function addCardItem(string $card_uuid, string $uuid, string $name, string $description, string $icon, $action, ?string $permission = null): void
    {
        if ($permission == null) {
            $permission = 'admin.settings.'.$card_uuid.'_'.$uuid;
        }
        $card = $this->cards->firstWhere('uuid', $card_uuid);
        if ($card) {
            $card->items->push(new SettingsItemDTO($card_uuid, $uuid, $name, $description, $icon, $action, $permission));
        }
    }

    public function getCurrentCard(string $uuid): ?SettingsCardDTO
    {
        return $this->cards->firstWhere('uuid', $uuid);
    }

    public function getCurrentItem(string $card_uuid, string $uuid): ?SettingsItemDTO
    {
        $card = $this->cards->firstWhere('uuid', $card_uuid);
        if ($card) {
            return $card->items->firstWhere('uuid', $uuid);
        }

        return null;
    }

    public function setSavedSettings(array $loadSettings): void
    {
        $this->savedSettings = collect($loadSettings);
    }

    public function initTranslatedSettings(): void
    {
        $this->translatedSettings = collect(\Cache::remember('translated_settings', 60 * 24, function () {
            $translations = Translation::where('model', Setting::class)->get();
            $settings = [];
            foreach ($translations as $translation) {
                if (! isset($settings[$translation->locale])) {
                    $settings[$translation->locale] = [];
                }
                $settings[$translation->locale][$translation->key] = $translation->content;
            }

            return $settings;
        }));
    }
}
