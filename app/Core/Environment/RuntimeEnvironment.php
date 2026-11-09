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
 * Year: 2026
 */

namespace App\Core\Environment;

use App\Helpers\EnvEditor;
use App\Models\ActionLog;
use App\Models\Admin\Setting;
use Dotenv\Dotenv;
use Illuminate\Support\Facades\Cache;

/**
 * Values the admin panel edits at runtime live in the settings table instead of
 * the .env file, so .env can stay read-only once installed. Bootstrap values
 * (APP_KEY, DB_*, APP_ENV, ADMIN_PREFIX...) are needed before the database is
 * reachable and therefore remain in .env.
 */
class RuntimeEnvironment
{
    private const SETTING_PREFIX = 'env_';

    private const MASK = '********';

    /**
     * Laravel's own .env notation for an empty string. Stored instead of deleting
     * the row, otherwise a cleared value would fall back to a stale .env entry.
     */
    private const EMPTY_VALUE = '(empty)';

    /**
     * Managed key => config path it overrides (null when read directly with runtime_env()).
     */
    public const MANAGED_KEYS = [
        'STRIPE_PRIVATE_KEY' => null,
        'STRIPE_PUBLIC_KEY' => null,
        'STRIPE_WEBHOOK_SECRET' => null,
        'STRIPE_PAYMENT_TYPES' => null,
        'PAYPAL_EMAIL' => null,
        'PAYPAL_SANDBOX' => null,
        'PAYPAL_CLIENT_ID' => null,
        'PAYPAL_CLIENT_SECRET' => null,
        'MOLLIE_KEY' => null,
        'MOLLIE_TEST_MODE' => null,
        'SUMUP_API_KEY' => null,
        'SUMUP_MERCHANT_CODE' => null,
        'SQUARE_ACCESS_TOKEN' => null,
        'SQUARE_LOCATION_ID' => null,
        'SQUARE_ENVIRONMENT' => null,
        'SQUARE_WEBHOOK_SIGNATURE_KEY' => null,
        'STANCER_SECRET_KEY' => null,
        'STANCER_PUBLIC_KEY' => null,
        'STORE_FIXED_VAT_RATE' => null,
        'STORE_VAT_COUNTRY' => null,
        'HASH_DRIVER' => 'hashing.driver',
        'TELEMETRY_ENABLED' => 'telemetry.enabled',
        'MAIL_MAILER' => 'mail.default',
        'MAIL_FROM_ADDRESS' => 'mail.from.address',
        'MAIL_FROM_NAME' => 'mail.from.name',
        'MAIL_HOST' => 'mail.mailers.smtp.host',
        'MAIL_PORT' => 'mail.mailers.smtp.port',
        'MAIL_USERNAME' => 'mail.mailers.smtp.username',
        'MAIL_PASSWORD' => 'mail.mailers.smtp.password',
        'MAIL_ENCRYPTION' => 'mail.mailers.smtp.encryption',
    ];

    /**
     * Encrypted at rest with APP_KEY and masked in the action log.
     */
    public const SECRET_KEYS = [
        'STRIPE_PRIVATE_KEY',
        'STRIPE_WEBHOOK_SECRET',
        'PAYPAL_CLIENT_SECRET',
        'MOLLIE_KEY',
        'SUMUP_API_KEY',
        'SQUARE_ACCESS_TOKEN',
        'SQUARE_WEBHOOK_SIGNATURE_KEY',
        'STANCER_SECRET_KEY',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
    ];

    public static function isManaged(string $key): bool
    {
        return array_key_exists($key, self::MANAGED_KEYS);
    }

    public static function settingName(string $key): string
    {
        return self::SETTING_PREFIX.strtolower($key);
    }

    public static function isSecretSetting(string $settingName): bool
    {
        foreach (self::SECRET_KEYS as $key) {
            if (self::settingName($key) === $settingName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Database value first, then .env: installs that have not saved the setting
     * yet (or whose database is unreachable) keep working unchanged.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::isManaged($key)) {
            $value = self::storedValue($key);
            if ($value !== null) {
                return $value;
            }
        }

        return env($key, $default);
    }

    /**
     * Strict boolean read: env() already turns "true" into true, so string
     * comparisons such as === 'true' silently never matched.
     */
    public static function flag(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Persist values; returns the bootstrap values that could not be written
     * because .env is read-only, so the caller can tell the admin what to set.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    public static function put(array $values): array
    {
        $managed = [];
        $bootstrap = [];
        foreach ($values as $key => $value) {
            if (self::isManaged($key)) {
                $normalized = self::normalize($value);
                $managed[self::settingName($key)] = $normalized === '' ? self::EMPTY_VALUE : $normalized;
            } else {
                $bootstrap[$key] = $value;
            }
        }

        if ($managed !== []) {
            self::storeManaged($managed);
        }

        return self::writeBootstrap($bootstrap);
    }

    /**
     * Copies managed values from the .env file into the settings table, leaving
     * the file untouched. The file is parsed directly because env() returns
     * nothing once the config is cached. Already stored settings win.
     *
     * @return array<int, string> imported keys
     */
    public static function importFromEnvFile(?string $path = null): array
    {
        $path ??= app()->environmentFilePath();
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }
        $values = Dotenv::parse((string) file_get_contents($path));
        $imported = [];
        foreach (array_keys(self::MANAGED_KEYS) as $key) {
            $value = $values[$key] ?? null;
            $name = self::settingName($key);
            if ($value === null || $value === '' || Setting::where('name', $name)->exists()) {
                continue;
            }
            Setting::create(['name' => $name, 'value' => $value]);
            $imported[] = $key;
        }
        if ($imported !== []) {
            Cache::forget('settings');
        }

        return $imported;
    }

    /**
     * Admin flash listing the .env lines left to set by hand. Values are escaped:
     * the warning alert renders raw HTML and they come from the submitted form.
     *
     * @param  array<string, string>  $pending
     */
    public static function pendingWarning(array $pending): ?string
    {
        if ($pending === []) {
            return null;
        }
        $lines = collect($pending)->map(fn (string $value, string $key) => e($key.'='.$value))->implode('<br>');

        return e(__('admin.settings.core.env_readonly')).'<br><code>'.$lines.'</code>';
    }

    /**
     * Overrides the config values computed from .env with the stored ones.
     */
    public static function applyToConfig(): void
    {
        foreach (self::MANAGED_KEYS as $key => $configPath) {
            if ($configPath === null) {
                continue;
            }
            $value = self::storedValue($key);
            if ($value !== null) {
                config([$configPath => $value]);
            }
        }
    }

    private static function storedValue(string $key): mixed
    {
        try {
            $value = setting(self::settingName($key));
        } catch (\Throwable) {
            return null;
        }

        if ($value === self::EMPTY_VALUE) {
            return '';
        }

        return $value === '' ? null : $value;
    }

    private static function normalize(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /** @param array<string, string> $settings */
    private static function storeManaged(array $settings): void
    {
        // Logged here with masked secrets: Setting::updateSettings would log them in clear.
        $old = Setting::updateSettings($settings, null, false);
        $mask = fn (array $values) => collect($values)
            ->map(fn ($value, $name) => self::isSecretSetting($name) && ! in_array($value, [null, '', self::EMPTY_VALUE], true) ? self::MASK : $value)
            ->all();
        if ($old != $settings) {
            ActionLog::log(ActionLog::SETTINGS_UPDATED, Setting::class, null, auth('admin')->id(), null, [], $mask($old), $mask($settings));
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    private static function writeBootstrap(array $values): array
    {
        $changed = collect($values)
            ->map(fn ($value) => self::normalize($value))
            ->filter(fn (string $value, string $key) => self::normalize(env($key)) !== $value)
            ->all();
        if ($changed === []) {
            return [];
        }
        $envPath = app()->environmentFilePath();
        if (! is_file($envPath) || ! is_writable($envPath)) {
            return $changed;
        }
        EnvEditor::updateEnv($changed);

        return [];
    }
}
