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

use App\Services\SettingsService;
use App\Services\Store\CurrencyService;

if (! function_exists('setting')) {
    function setting(?string $name = null, mixed $default = null): mixed
    {
        /** @var SettingsService $settings */
        $settings = app('settings');

        if ($name === null) {
            return $settings;
        }
        $name = str_replace('.', '_', $name);

        return $settings->get($name, $default);
    }
}

if (! function_exists('setting_is_saved')) {
    function setting_is_saved(string $key): bool
    {
        return in_array($key, app('settings')->savedSettings->keys()->toArray());
    }
}

if (! function_exists('translated_setting')) {
    function translated_setting(string $key, mixed $default = null, ?string $locale = null): mixed
    {
        return \App\Models\Admin\Setting::getTranslationsForKey($key, $default, $locale);
    }
}
if (! function_exists('ClientX\varExport')) {
    function varExport($expression, $return = false)
    {
        $export = var_export($expression, true);
        $patterns = [
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
            "/=>[ ]?\n[ ]+\[/" => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
        ];
        $export = preg_replace(array_keys($patterns), array_values($patterns), $export);
        if ((bool) $return) {
            return $export;
        } else {
            echo $export;
        }
    }
}

if (! function_exists('basket')) {
    function basket(bool $force = true)
    {
        return \App\Models\Store\Basket\Basket::getBasket($force);
    }
}

if (! function_exists('is_installed')) {
    function is_installed(): bool
    {
        return file_exists(storage_path('installed'));
    }
}

if (! function_exists('is_demo')) {
    function is_demo(): bool
    {
        return file_exists(storage_path('demo'));
    }
}

if (! function_exists('is_darkmode')) {
    function is_darkmode(bool $admin = false): bool
    {
        if (setting('theme_switch_mode', 'both') == 'dark') {
            return true;
        }
        if (! $admin && auth('web')->check()) {
            return auth('web')->user()->dark_mode;
        }
        if ($admin && auth('admin')->check()) {
            return auth('admin')->user()->dark_mode;
        }
        if (\Illuminate\Support\Facades\Session::get('dark_mode', false)) {
            return true;
        }

        $defaultMode = app('theme')->getTheme()->json['default_theme_mode'] ?? 'both';

        return \Illuminate\Support\Facades\Session::get('dark_mode', setting('theme_switch_mode', $defaultMode) == 'dark');
    }
}

if (! function_exists('is_gdpr_compliment')) {
    function is_gdpr_compliment(): bool
    {
        if (auth('web')->check()) {
            return auth('web')->user()->gdpr_compliment;
        }
        if (\Illuminate\Support\Facades\Session::get('gdpr_compliment', false)) {
            return true;
        }

        return false;
    }
}
if (! function_exists('is_lightmode')) {
    function is_lightmode(bool $admin = false): bool
    {
        return ! is_darkmode($admin);
    }
}

if (! function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $decimals = 2, bool $suffix = true)
    {

        $bytes = (int) $bytes;
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, $decimals).($suffix ? ' GB' : '');
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, $decimals).($suffix ? ' MB' : '');
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, $decimals).($suffix ? ' KB' : '');
        } elseif ($bytes > 1) {
            $bytes = $bytes.($suffix ? ' bytes' : '');
        } elseif ($bytes == 1) {
            $bytes = $bytes.($suffix ? ' byte' : '');
        } else {
            $bytes = '0 '.($suffix ? ' byte' : '');
        }

        return $bytes;
    }

}
if (! function_exists('currency')) {
    function currency(): string
    {
        return app(CurrencyService::class)->retrieveCurrency();
    }

    function currency_symbol(?string $currency = null): string
    {
        return app(CurrencyService::class)->get($currency ?? currency())['symbol'];
    }

    function currencies()
    {
        return app(CurrencyService::class)->getCurrencies();
    }

    function tax_percent(?string $iso = null): float
    {
        return \App\Services\Store\TaxesService::getVatPercent($iso);
    }

    function formatted_price(float $price, ?string $currency = null): string
    {
        $currency = $currency ?? currency();
        $locale = $currency == 'USD' ? 'en_US' : 'fr_FR';

        return (new NumberFormatter($locale, NumberFormatter::CURRENCY))->formatCurrency($price, $currency);
    }
}
if (! function_exists('module_path')) {
    function module_path(string $uuid, string $path = ''): string
    {
        return app('module')->modulePath($uuid, $path);
    }
}

if (! function_exists('addon_path')) {
    function addon_path(string $uuid, string $path = ''): string
    {
        return app('addon')->addonPath($uuid, $path);
    }
}
if (! function_exists('theme_manager')) {
    function theme_manager(): \App\Theme\ThemeManager
    {
        return app(\App\Theme\ThemeManager::class);
    }
}
if (! function_exists('is_subroute')) {
    function is_subroute($route): bool
    {
        if (is_array($route)) {
            return in_array(request()->path(), $route);
        }
        if ($route instanceof \App\Models\Personalization\MenuLink) {
            $route = $route->trans('url');
        }
        if ($route == '/' || $route == '/client') {
            return request()->path() == '/' || request()->path() == 'client';
        }
        if (Str::startsWith($route, 'http://') || Str::startsWith($route, 'https://')) {
            $route = parse_url($route, PHP_URL_PATH);
        }

        return Str::startsWith('/'.request()->path(), $route);
    }
}
if (! function_exists('ctx_version')) {
    function ctx_version(): string
    {
        return \App\Providers\AppServiceProvider::VERSION;
    }
}
if (! function_exists('is_tax_included')) {

    function is_tax_included()
    {
        return setting('store.mode_tax') == \App\Services\Store\TaxesService::MODE_TAX_INCLUDED;
    }

    function is_tax_excluded()
    {
        return setting('store.mode_tax') == \App\Services\Store\TaxesService::MODE_TAX_EXCLUDED;
    }
}

if (! function_exists('formatted_extension_list')) {
    function formatted_extension_list($string): string
    {
        $extensions = explode(',', $string);
        $appenddot = function ($ext) {
            return '.'.$ext;
        };
        $appenddot = array_map($appenddot, $extensions);

        return implode(', ', $appenddot);
    }
}

if (! function_exists('staff_has_permission')) {
    function staff_has_permission(string $permission): bool
    {
        if (auth('admin')->check()) {
            return auth('admin')->user()->can($permission);
        }

        return false;
    }

    function staff_aborts_permission(string $permission): void
    {
        abort_if(! staff_has_permission($permission), 403);
    }
}
if (! function_exists('admin_prefix')) {
    function admin_prefix(?string $path = null): string
    {
        if ($path) {
            return env('ADMIN_PREFIX', 'admin').'/'.$path;
        }

        return env('ADMIN_PREFIX', 'admin');
    }
}
if (! function_exists('theme_config')) {
    function theme_config(string $key, mixed $default = null): mixed
    {
        return app('theme')->getTheme()->config[$key] ?? $default;
    }
}

if (! function_exists('theme_metadata')) {
    function theme_metadata(string $key, mixed $default = null): mixed
    {
        $metadata = app('theme')->getTheme()->json['metadatas'] ?? [];

        return $metadata[$key] ?? $default;
    }
}
if (! function_exists('theme_section')) {
    function theme_section(string $uuid): \App\DTO\Core\Extensions\ThemeSectionDTO
    {
        return app('extension')->getThemeSection($uuid);
    }
}

if (! function_exists('render_theme_sections')) {
    function render_theme_sections()
    {
        $url = request()->path();
        if (! str_starts_with($url, '/')) {
            $url = '/'.$url;
        }

        $themeManager = app('theme');

        return collect($themeManager->getSectionsForUrl($url))->reduce(function (string $html, \App\Models\Personalization\Section $section) use ($themeManager) {
            try {
                $themeManager->setCurrentRenderingSection($section);
                $rendered = $section->toDTO()->render();
            } finally {
                $themeManager->setCurrentRenderingSection(null);
            }

            return $html.$rendered;
        }, '');
    }
}

if (! function_exists('section_config')) {
    /**
     * Get a configuration value for the current rendering section
     *
     * @param  string  $key  The configuration key
     * @param  mixed  $default  Default value if not configured
     */
    function section_config(string $key, mixed $default = null): mixed
    {
        try {
            $section = app('theme')->getCurrentRenderingSection();

            if (! $section) {
                return $default;
            }

            $fieldDef = $section->getFieldDefinition($key);
            $fieldDefault = $fieldDef['default'] ?? $default;

            return $section->getSetting($key, $fieldDefault);
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (! function_exists('section_config_all')) {
    /**
     * Get all configuration values for the current rendering section
     */
    function section_config_all(): array
    {
        $section = app('theme')->getCurrentRenderingSection();

        if (! $section) {
            return [];
        }

        $fields = $section->getConfigurableFields();
        $config = [];

        foreach ($fields as $field) {
            $config[$field['key']] = $section->getSetting(
                $field['key'],
                $field['default'] ?? null
            );
        }

        return $config;
    }
}

if (! function_exists('extension_view')) {
    function extension_view(string $view, ?string $uuid = null, array $data = [], ?string $file = null)
    {
        if (str_contains(request()->path(), admin_prefix())) {
            View::share('share_prefix', 'admin/');
            if ($uuid == null) {
                // Cela permet de charger les vues dans le dossier resources/themes/default/views et non le thème actif. CF WebhostingProductData
                // Avec les extensions, nous avons le namespace avec la variable $uuid, mais pour les vues de base, nous devons forcer le nom du fichier avec la variable $view
                return view()->file(resource_path($file), $data);
            }

            return view($uuid.'_default::'.$view, $data);
        }
        View::share('share_prefix', '');
        if ($uuid == null) {
            return view($view, $data);
        }

        return view($uuid.'::'.$view, $data);
    }
}

if (! function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        return asset('themes/'.app('theme')->getTheme()->uuid.'/'.$path);
    }
}

if (! function_exists('generate_uuid')) {
    function generate_uuid(string $class): string
    {
        $uuid = (string) substr(\Illuminate\Support\Str::uuid(), 0, 8);
        if ($class::where('uuid', $uuid)->exists()) {
            return generate_uuid($class);
        }

        return $uuid;
    }
}

if (! function_exists('sanitize_content')) {
    function sanitize_content(string $content): string
    {
        if (str_contains($content, '%%')) {
            $content = str_replace('%%', '%', $content);
        }

        $badPatterns = [
            '/<\?(?:php|=)?/i',
            '/@php\b/i',
            '/\{\!\!.*?\!\!\}/s',
            '/@(include|extends|component|each|includeIf|includeWhen)\s*\(/i',
        ];

        foreach ($badPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace_callback($pattern, function ($m) {
                    return '&lt;?';
                }, $content);
            }
        }

        return $content;
    }
}

if (! function_exists('is_sanitized')) {
    function is_sanitized(string $content): bool
    {
        $badPatterns = [
            '/<\?(?:php|=)?/i',
            '/@php\b/i',
            '/\{\!\!.*?\!\!\}/s',
            '/@(include|extends|component|each|includeIf|includeWhen)\s*\(/i',
        ];

        foreach ($badPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        return true;
    }
}
if (! function_exists('get_group_icon')) {
    function get_group_icon(string $name): string
    {
        $name = mb_strtolower($name, 'UTF-8');
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $icon = 'bi bi-cloud text-xl';
        $map = [
            'vps' => 'bi bi-server',
            'hosting' => 'bi bi-globe',
            'hebergement' => 'bi bi-globe',
            'dedicated' => 'bi bi-hdd-rack',
            'dedie' => 'bi bi-hdd-rack',
            'domain' => 'bi bi-globe2',
            'domaine' => 'bi bi-globe2',
            'fivem' => 'bi bi-controller',
            'gmod' => 'bi bi-joystick',
            'garry' => 'bi bi-joystick',
            'ark' => 'bi bi-rocket-takeoff',
            'minecraft' => 'bi bi-box',
            'rust' => 'bi bi-fire',
            'valheim' => 'bi bi-shield',
            'palworld' => 'bi bi-stars',
            'cs2' => 'bi bi-bullseye',
            'csgo' => 'bi bi-bullseye',
            'dayz' => 'bi bi-compass',
            'terraria' => 'bi bi-tree',
            'satisfactory' => 'bi bi-gear',
        ];
        foreach ($map as $key => $cls) {
            if (str_contains($name, $key)) {
                $icon = $cls.' text-xl';
                break;
            }
        }

        if ($icon === 'bi bi-cloud text-xl' && preg_match('/\bmc\b/', $name)) {
            $icon = 'bi bi-box text-xl';
        }

        return $icon;
    }
}
