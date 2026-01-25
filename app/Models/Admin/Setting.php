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

namespace App\Models\Admin;

use App\Models\ActionLog;
use App\Models\Personalization\Translation;
use App\Models\Traits\Translatable;
use App\Services\Core\LocaleService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Str;

/**
 * @property int $id
 * @property string $name
 * @property mixed|null $value
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Translation> $translations
 * @property-read int|null $translations_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValue($value)
 *
 * @mixin \Eloquent
 */
class Setting extends Model
{
    use HasFactory;
    use Translatable;

    public static $ignoreLogAttributes = [
        'app_license_refresh_token',
        'app_cron_last_run',
    ];

    protected $fillable = [
        'name',
        'value',
    ];

    public $timestamps = false;

    public $encrypt = [
        'mail_smtp_username',
        'mail_smtp_password',
    ];

    protected static array $ignoreKeys = [
        'app_cron_last_run',
        'app_license_refresh_token',
    ];

    /**
     * Modify a given settings values and return the previous values.
     */
    public static function updateSettings(string|array $key, mixed $value = null, bool $log = true): array
    {
        $keys = is_array($key) ? $key : [$key => $value];
        $old = collect($keys)->mapWithKeys(fn ($value, $name) => [
            $name => setting()->savedSettings->get($name),
        ])->all();

        foreach ($keys as $name => $val) {
            if ($val !== null && $val !== '') {
                self::updateOrCreate(['name' => $name], ['value' => $val]);
            } else {
                // Delete setting and associated translations to prevent orphan data
                $setting = self::where('name', $name)->first();
                if ($setting) {
                    Translation::where('model', self::class)
                        ->where('model_id', $setting->id)
                        ->delete();
                    $setting->delete();
                }
            }

            setting()->set($name, $val);
        }
        if ($log) {
            if (! empty(array_diff(array_keys($keys), self::$ignoreLogAttributes)) && ! empty(array_diff(array_values($keys), array_values($old)))) {
                ActionLog::log(ActionLog::SETTINGS_UPDATED, Setting::class, null, auth('admin')->id(), null, [], $old, $keys);
            }
        }

        \Cache::forget('settings');

        // Clear translation cache to ensure fresh data on next request
        foreach ($keys as $name => $val) {
            \Cache::forget('translations_setting_' . $name);
        }

        return $old;
    }

    public function getValueAttribute(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (Str::is($this->encrypted, $this->name)) {
            try {
                return decrypt($value, false);
            } catch (DecryptException $e) {
                return $value;
            }
        }

        return $value;
    }

    public static function getTranslationsForKey(string $key, ?string $default = null, ?string $locale = null)
    {

        if ($locale == null) {
            $locale = LocaleService::fetchCurrentLocale();
        }
        $cacheKey = 'translations_setting_'.$key;
        $translations = Cache::rememberForever($cacheKey, function () use ($key) {
            return Translation::where('model', Setting::class)->where('key', $key)->get()->mapWithKeys(function ($translation) {
                return [$translation->locale => $translation->content];
            });
        });
        if (isset($translations[$locale])) {
            return $translations[$locale];
        }

        return $default ?? setting($key);
    }
}
