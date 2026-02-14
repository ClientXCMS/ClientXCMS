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

namespace App\Models\Traits;

use App\Models\Personalization\Translation;
use App\Services\Core\LocaleService;
use Illuminate\Support\Facades\Cache;

trait Translatable
{
    protected static function bootTranslatable()
    {
        static::deleting(function ($model) {
            $model->translations()->delete();
            Cache::forget('translations_' . self::class . '_' . $model->id);
        });
    }

    public function translatableKeys(): array
    {
        return $this->translatableKeys ?? [];
    }

    public function translations()
    {
        return $this->hasMany(Translation::class, 'model_id')->where('model', self::class);
    }

    public function getTranslation(string $key, ?string $default = null, ?string $locale = null): string
    {
        if ($locale == null) {
            $locale = LocaleService::fetchCurrentLocale();
        }
        $cacheKey = 'translations_' . self::class . '_' . $this->id;
        $translations = Cache::rememberForever($cacheKey, function () {
            return $this->translations()->get()->keyBy(function ($translation) {
                return $translation->key . '_' . $translation->locale;
            });
        });
        $translationKey = $key . '_' . $locale;
        if (isset($translations[$translationKey])) {
            return $translations[$translationKey]->content;
        }

        return $default ?? $this->{$key} ?? '';
    }

    public function trans(string $key, ?string $default = null, ?string $locale = null): string
    {
        return $this->getTranslation($key, $default, $locale);
    }

    public function saveTranslations(string $key, array $translations): void
    {
        foreach ($translations as $locale => $content) {
            $this->saveTranslation($key, $locale, $content);
        }
        Cache::forget('translations_' . self::class . '_' . $this->id);
    }

    public function saveTranslation(string $key, string $locale, string $content)
    {
        $translation = $this->translations()->where('key', $key)->where('locale', $locale)->first();
        if ($translation == null) {
            $this->translations()->create([
                'key' => $key,
                'model' => self::class,
                'model_id' => $this->id,
                'locale' => $locale,
                'content' => $content,
            ]);
        } else {
            $translation->update([
                'content' => $content,
            ]);
        }

        Cache::forget('translations_' . self::class . '_' . $this->id);
    }
}
