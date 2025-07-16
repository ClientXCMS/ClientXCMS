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
namespace App\Services\Core;

use App\Models\Admin\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;

class LocaleService
{
    const DOWNLOAD_ENDPOINT = 'https://api.github.com/repos/DarkIncognito85/ctx-translations/contents/';

    const DEFAULT_ENABLED_LOCALES = '["fr_FR"]';

    public static ?string $currentLocale = null;

    public static function getLocalesNames(bool $onlyEnabled = true, bool $removeDefault = false)
    {
        return collect(self::getLocales($onlyEnabled, $removeDefault))->mapWithKeys(function ($locale, $key) {
            return [$key => $locale['name']];
        })->toArray();
    }

    public static function fetchCurrentLocale(): string
    {
        if (self::$currentLocale) {
            return self::$currentLocale;
        }
        $locales = array_keys(self::getLocalesNames());
        if (auth('web')->check()) {
            $locale = auth('web')->user()->locale;
            if (in_array($locale, $locales)) {
                return $locale;
            }
        }
        if (auth('admin')->check()) {
            $locale = auth('admin')->user()->locale;
            if (in_array($locale, $locales)) {
                return $locale;
            }
        }
        $cookie = Cookie::get('locale');
        if (in_array($cookie, $locales)) {
            return $cookie;
        }

        return setting('app_default_locale', 'en_GB');
    }

    public static function getLocales(bool $onlyEnabled = true, bool $removeDefault = false)
    {
        $locales = self::getLocalesFromAPI();
        if ($onlyEnabled) {
            $locales = collect($locales)->filter(function ($locale) {
                return $locale['is_enabled'] && $locale['is_downloaded'];
            });
        }
        if ($removeDefault) {
            $locales = collect($locales)->filter(function ($locale) {
                return ! $locale['is_default'];
            });
        }

        return $locales->toArray();
    }

    /**
     * Set the locale in the application
     *
     * @return void
     */
    public static function setLocale(string $locale)
    {
        if (str_contains($locale, '_')) {
            [$locale, $country] = explode('_', $locale);
            self::$currentLocale = $locale.'_'.strtoupper($country);
        }
        app()->setLocale($locale);
    }

    /**
     * Persist the locale in the database and in a cookie
     */
    public static function saveLocale(string $locale): \Illuminate\Http\RedirectResponse
    {
        if (auth('web')->check()) {
            auth('web')->user()->update(['locale' => $locale]);
        }
        if (auth('admin')->check()) {
            auth('admin')->user()->update(['locale' => $locale]);
        }
        Cookie::queue('locale', $locale, 60 * 24 * 365, null, null, false, false);

        return redirect()->back();
    }

    public static function downloadFiles(string $locale)
    {
        $locales = collect(self::getLocales(false))->keys()->toArray();
        if (! in_array($locale, $locales)) {
            throw new \Exception('The locale file could not be downloaded. The locale is not available.');
        }
        [$locale, $country] = explode('_', $locale);
        $http = \Http::get(self::DOWNLOAD_ENDPOINT."/translations/{$locale}.json");
        if ($http->status() !== 200) {
            throw new \Exception('The locale file could not be downloaded. Status code: '.$http->status());
        }
        \Storage::put("{$locale}.json", base64_decode($http->json()['content']));
        \Artisan::call('translations:import-file', ['--path' => "app/{$locale}.json"]);
        \Storage::delete("{$locale}.json");
        \Cache::forget('locales');

        return back();
    }

    public static function getLocalesFromAPI()
    {
        return Cache::rememberForever('locales', function () {
            $http = \Http::get(self::DOWNLOAD_ENDPOINT.'/locales.json');
            if ($http->status() !== 200) {
                $content = json_decode(self::getLocalesFromLocal(), true);
            } else {
                $content = json_decode(base64_decode($http->json()['content']), true);
            }

            return collect($content)->mapWithKeys(function ($locale, $key) {
                return [$key => [
                    'key' => $locale['key'],
                    'name' => $locale['name'],
                    'flag' => $locale['flag'],
                    'last_check' => Carbon::now()->format('Y-m-d H:i:s'),
                    'is_downloaded' => self::isLocaleDownloaded($locale['key']),
                    'is_default' => self::isLocaleDefault($key),
                    'is_enabled' => self::isLocaleEnabled($key),
                ]];
            });
        });
    }

    public static function toggleLocale(string $key)
    {
        $enabled = json_decode(setting('app_enabled_locales', self::DEFAULT_ENABLED_LOCALES), true);
        if (in_array($key, $enabled)) {
            $enabled = array_diff($enabled, [$key]);
        } else {
            $enabled[] = $key;
        }
        Setting::updateSettings(['app_enabled_locales' => json_encode($enabled)]);
        \Cache::forget('locales');
    }

    private static function isLocaleDownloaded(string $key): bool
    {
        return file_exists(base_path('lang/'.$key));
    }

    private static function isLocaleDefault(string $key): bool
    {
        return setting('app_default_locale') === $key;
    }

    private static function isLocaleEnabled(string $key): bool
    {
        $enabled = json_decode(setting('app_enabled_locales', self::DEFAULT_ENABLED_LOCALES), true);

        return in_array($key, $enabled);
    }

    public static function storeTranslations(string $model, int $model_id, array $translations)
    {
        collect($translations)->map(function ($array, $locale) use ($model, $model_id) {
            $translations = collect($array)->filter(function ($key, $array) {
                return $key != null;
            })->toArray();
            $model = $model::find($model_id);
            foreach ($translations as $key => $value) {
                $model->saveTranslation($key, $locale, $value);
            }
        });
    }

    public static function storeSettingsTranslations(array $translations)
    {
        $settings = new Setting;
        foreach ($translations as $locale => $values) {
            foreach ($values as $key => $value) {
                if ($value == null) {
                    continue;
                }
                $model = Setting::where('name', $key)->first();
                if (! $model) {
                    continue;
                }
                $settings->id = $model->id;
                $settings->saveTranslation($key, $locale, $value);
                \Cache::forget('translations_setting_'.$key);
            }
        }

    }

    private static function getLocalesFromLocal()
    {
        return file_get_contents(resource_path('locales.json'));
    }

    public static function isValideLocale(string $locale)
    {
        return in_array($locale, array_keys(self::getLocalesNames()));
    }
}
