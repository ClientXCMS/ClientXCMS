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


namespace App\Theme;

use App\DTO\Core\Extensions\ExtensionThemeDTO;
use App\DTO\Core\Extensions\SectionTypeDTO;
use App\Models\Admin\Setting;
use App\Models\Personalization\MenuLink;
use App\Models\Personalization\Section;
use App\Models\Personalization\SocialNetwork;
use App\Models\Store\Group;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ThemeManager
{
    private ?ExtensionThemeDTO $theme = null;

    private string $themesPath;

    private array $themes;

    private string $themesPublicPath;

    public function __construct()
    {
        $this->themesPath = resource_path('themes/');
        $this->themesPublicPath = public_path('themes/');
        $this->scanThemes();

        if ($this->getTheme() != null) {
            app('view')->addLocation($this->themePath('views'));
            app('view')->addLocation($this->themePath());
            if (File::exists($this->themePath('lang'))) {
                app('translator')->addNamespace('theme', $this->themePath('lang'));
            }
        }
    }

    /**
     * Cache key prefix for theme configuration.
     * Cache is stored per locale to support multilingual sections.
     */
    private const CACHE_KEY_PREFIX = 'theme_configuration';

    /**
     * Clear theme configuration cache for all locales.
     */
    public static function clearCache(): void
    {
        // Clear cache for all enabled locales
        $enabledLocales = self::getEnabledLocales();
        foreach ($enabledLocales as $locale) {
            Cache::forget(self::CACHE_KEY_PREFIX . '_' . $locale);
        }

        // Also clear legacy cache key for backwards compatibility
        Cache::forget(self::CACHE_KEY_PREFIX);
    }

    /**
     * Get list of enabled locales for cache management.
     */
    private static function getEnabledLocales(): array
    {
        try {
            $setting = \App\Models\Admin\Setting::where('name', 'app_enabled_locales')->first();
            if ($setting && $setting->value) {
                $locales = json_decode($setting->value, true);
                if (is_array($locales) && !empty($locales)) {
                    return $locales;
                }
            }
        } catch (\Exception $e) {
            // Database might not be available during boot
        }

        // Fallback to common locales
        return ['fr', 'en', 'de', 'es', 'it', 'pt', 'nl'];
    }

    public function hasTheme(): bool
    {
        return $this->theme !== 'default';
    }

    public function getTheme(): ExtensionThemeDTO
    {
        return $this->theme;
    }

    public function setTheme(string $theme, bool $save = false): void
    {
        $oldTheme = $this->theme;
        $this->theme = collect($this->themes)->first(function ($item) use ($theme) {
            return $item->uuid == $theme;
        });
        Setting::updateSettings(['theme' => $theme]);
        $sections1 = $this->fetchThemeSection($oldTheme);
        $sections2 = $this->fetchThemeSection($this->theme);
        $existing = collect($sections1)->pluck('uuid')->intersect(collect($sections2)->pluck('uuid'));
        foreach ($existing as $uuid) {
            $sections = Section::where('uuid', $uuid)->get();
            foreach ($sections as $section) {
                $section->theme_uuid = $this->theme->uuid;
                $section->save();
                if (File::exists($oldTheme->path.'/views/sections_copy/'.$section->id.'-'.$section->uuid.'.blade.php')) {
                    try {
                        File::copy($oldTheme->path.'/views/sections_copy/'.$section->id.'-'.$section->uuid.'.blade.php', $this->theme->path.'/views/sections_copy/'.$section->id.'-'.$section->uuid.'.blade.php');
                    } catch (\Exception $e) {
                        // do nothing
                    }
                }
            }
        }
        $this->createAssetsLink($theme);
    }

    public function themePath(string $path = '', ?string $theme = null): ?string
    {
        if ($theme === null) {
            if (! $this->theme) {
                return null;
            }
            $theme = $this->theme->path;
        }

        return "{$theme}/{$path}";
    }

    public function themesPath(string $path = ''): string
    {
        return $this->themesPath.$path;
    }

    public function themesPublicPath(string $path = ''): string
    {
        return $this->themesPublicPath.$path;
    }

    public function getSocialsNetworks()
    {
        return $this->getSetting()['socials'] ?? collect();
    }

    /**
     * @return \Illuminate\Support\Collection<MenuLink>
     */
    public function getBottomLinks(): \Illuminate\Support\Collection
    {
        return $this->getCustomLinks('bottom');
    }

    public function getCustomLinks(string $type): Collection
    {
        if (app()->environment('testing')) {
            return collect();
        }
        $support = $this->getTheme()->supportOption('menu_dropdown');
        $items = $this->getSetting()[$type . '_links'] ?? collect();

        return $items->filter(function (MenuLink $item) use ($support) {
            return $item->canShowed($support);
        });
    }

    public function getFrontLinks(): \Illuminate\Support\Collection
    {
        return $this->getCustomLinks('front');
    }

    public function getSections(): Collection
    {
        return $this->getSetting()['sections'] ?? collect();
    }

    public function getSectionsForUrl(string $url): Collection
    {
        $theme_uuid = $this->getTheme()->uuid;

        return $this->getSections()->where('url', $url)->where('theme_uuid', $theme_uuid)->where('is_active', true);
    }

    public function isThemeSectionActive(string $uuid): bool
    {
        $theme_uuid = $this->getTheme()->uuid;

        return $this->getSections()->where('uuid', $uuid)->where('theme_uuid', $theme_uuid)->where('is_active', true)->exists();
    }

    /**
     * Get theme settings with locale-aware caching.
     * Sections HTML are cached per locale to support translations.
     */
    public function getSetting()
    {
        $locale = app()->getLocale();
        $cacheKey = self::CACHE_KEY_PREFIX . '_' . $locale;

        return Cache::remember($cacheKey, 60 * 60 * 24 * 7, function () {
            $types = \App\Models\Personalization\MenuLink::pluck('type')->unique()->toArray();
            $links = collect($types)->mapWithKeys(function ($type) {
                return [$type . '_links' => MenuLink::where('type', $type)->whereNull('parent_id')->orderBy('position')->get()];
            });
            return $links->merge([
                'socials' => SocialNetwork::all()->where('hidden', false),
                'sections_pages' => $this->getSectionsPages(),
                'sections' => Section::orderBy('order')->get(),
                'sections_html' => Section::orderBy('order')->get()->mapWithKeys(function (Section $item) {
                    return [$item->path => $item->toDTO()->render(false)];
                }),
            ]);
        });
    }

    public function themeExists(string $theme): bool
    {
        return file_exists($this->themesPath.$theme);
    }

    public function publicPath(string $path = '', ?string $theme = null): ?string
    {
        if ($theme === null) {
            if (! $this->hasTheme()) {
                return null;
            }

            $theme = $this->theme->path;
        }

        return $this->themesPublicPath("{$theme}/{$path}");
    }

    public function scanThemes()
    {
        $this->themes = [];
        if (! empty($this->themes)) {
            return;
        }
        foreach (File::directories($this->themesPath) as $theme) {
            if (File::exists($theme.'/theme.json') && $theme != $this->themesPath.'default') {
                $this->themes[] = ExtensionThemeDTO::fromJson($theme.'/theme.json');
            }
        }
        if (! is_dir($this->themesPath.'/default')) {
            throw new \Exception('Default theme is missing');
        }
        array_unshift($this->themes, ExtensionThemeDTO::fromJson($this->themesPath.'/default/theme.json'));
        if ($this->theme == null) {
            $currentTheme = \setting('theme', 'default');
            if ($currentTheme && ! empty($this->themes)) {
                $this->theme = collect($this->themes)->first(function ($theme) use ($currentTheme) {
                    return $theme->uuid == $currentTheme;
                });
                if ($this->theme == null) {
                    $this->theme = collect($this->themes)->first();
                }
            }
        }
        $this->mergeWithExtensions();
    }

    public function getThemes(): array
    {
        if (empty($this->themes)) {
            $this->scanThemes();
        }

        return $this->themes;
    }

    public function getSectionsPages(bool $filter = true)
    {
        $pages['home'] = [
            'title' => __('personalization.sections.pages.page_home'),
            'url' => route('home', [], false),
            'icon' => 'bi bi-house',
        ];
        $pages['store'] = [
            'title' => __('personalization.sections.pages.page_store'),
            'url' => route('front.store.index', [], false),
            'icon' => 'bi bi-shop',
        ];
        $pages['checkout'] = [
            'title' => __('personalization.sections.pages.page_checkout'),
            'url' => route('front.store.basket.checkout', [], false),
            'icon' => 'bi bi-cart-check',
        ];
        $pages['basket'] = [
            'title' => __('personalization.sections.pages.page_basket'),
            'url' => route('front.store.basket.show', [], false),
            'icon' => 'bi bi-cart',
        ];
        $sections = Section::orderBy('order')->get();
        foreach (Group::getAvailable()->get() as $group) {
            $pages['group_'.$group->slug] = [
                'title' => __('personalization.sections.pages.page_group', ['name' => $group->name]),
                'url' => $group->route(false),
                'icon' => 'bi bi-boxes',
            ];
        }
        if ($filter) {
            $theme_uuid = $this->getTheme()->uuid;
            foreach ($pages as $uuid => $detail) {
                $pages[$uuid]['sections'] = $sections->where('url', $detail['url'])->where('theme_uuid', $theme_uuid)->sortBy('sort')->values();
            }
        }

        return $pages;
    }

    public function getSectionsTypes()
    {
        return Cache::get('sections_types', function () {
            return collect(Http::get('https://clientxcms.com/api/sections_types')->json('data'))->map(function ($item) {
                return new SectionTypeDTO($item, $this->getThemeSections());
            });
        });
    }

    public function getThemeSections(): array
    {
        return (array) Cache::remember('themes_sections', 60 * 60 * 24 * 7, function () {
            return $this->fetchThemeSection($this->getTheme());
        });
    }

    private function fetchThemeSection(ExtensionThemeDTO $dto)
    {
        $sections = $dto->getSections();
        $extensions = app('extension')->getAllExtensions();
        foreach ($extensions as $extension) {
            $sections = array_merge($sections, $extension->getSections());
        }

        return $sections;
    }

    protected function createAssetsLink(string $theme): void
    {
        if (File::exists($this->publicPath('', $theme))) {
            return;
        }

        $themeAssetsPath = $this->themePath('assets');
        if (File::exists($themeAssetsPath)) {
            $this->relativeLink($themeAssetsPath, $this->publicPath('', $theme));
        }
    }

    private function relativeLink(string $target, string $link): void
    {
        windows_os() ? File::link($target, $link) : File::relativeLink($target, $link);
    }

    public static function getColorsArray()
    {
        $file = storage_path('app'.DIRECTORY_SEPARATOR.'theme.json');
        if (file_exists($file)) {
            $theme = json_decode(file_get_contents($file), true);
        } else {
            $theme = [
                '50' => '#f0f5ff',
                '100' => '#e5edff',
                '200' => '#cddbfe',
                '300' => '#b4c6fc',
                '400' => '#8da2fb',
                '500' => '#6875f5',
                '600' => '#5850ec',
                '700' => '#5145cd',
                '800' => '#42389d',
                '900' => '#362f78',
            ];
        }

        return $theme;
    }

    public static function getContrastColor($hexColor): string
    {
        $hexColor = ltrim($hexColor, '#');
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? 'black' : 'white';
    }

    private function mergeWithExtensions()
    {
        $themes = collect(app('extension')->fetch()['items'] ?? [])->filter(function ($item) {
            return $item['type'] == 'theme';
        });
        foreach ($themes as $theme) {
            if (collect($this->themes)->where('uuid', $theme['uuid'])->count() == 1) {
                $current = collect($this->themes)->where('uuid', $theme['uuid'])->first();
                $current->api = $theme;
            } else {
                $this->themes[] = ExtensionThemeDTO::fromApi($theme);
            }
        }
    }
}
