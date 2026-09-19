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
use App\Exceptions\ThemeInvalidException;
use App\Extensions\ExtensionManager;
use App\Models\Admin\Setting;
use App\Models\Personalization\MenuLink;
use App\Models\Personalization\Section;
use App\Models\Personalization\SocialNetwork;
use App\Models\Store\Group;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ThemeManager
{
    private ?ExtensionThemeDTO $theme = null;

    private string $themesPath;

    private array $themes;

    private string $themesPublicPath;

    private ?Section $currentRenderingSection = null;

    /**
     * Menu links are kept separately from the full theme configuration because
     * sections can request a menu while that configuration is being rendered.
     * Reading them through getSetting() in that situation causes an endless
     * Cache::remember() recursion and eventually an empty HTTP 500 response.
     *
     * @var array<string, Collection>
     */
    private array $menuLinks = [];

    private const CACHE_KEY_PREFIX = 'theme_configuration';

    public function __construct()
    {
        $this->themesPath = resource_path('themes/');
        $this->themesPublicPath = public_path('themes/');
        $this->scanThemes();

        if ($this->getTheme() != null) {
            app('view')->addLocation($this->themePath('views'));
            app('view')->addLocation($this->themePath());
            $this->registerTranslations();
            $this->registerThemeSeeders();
            $this->bootTheme();
        }
    }

    /**
     * ThemeManager can be resolved while extension service providers are still
     * registering. At that point Laravel's translator may not be bound yet, so
     * defer the namespace registration instead of forcing an early resolution.
     */
    private function registerTranslations(): void
    {
        $langPath = $this->themePath('lang');
        if (! File::exists($langPath)) {
            return;
        }

        app()->booted(function () use ($langPath) {
            app('translator')->addNamespace('theme', $langPath);
        });
    }

    protected function bootTheme(): void
    {
        $bootFile = $this->themePath('boot.php');

        if ($bootFile && File::exists($bootFile)) {
            require $bootFile;
        }
    }

    protected function registerThemeSeeders(): void
    {
        if (! $this->theme || ! $this->theme->hasSeeder()) {
            return;
        }

        $seederClass = $this->theme->loadSeeder();
        if ($seederClass !== null) {
            app('extension')->addSeeder($seederClass);
        }
    }

    public static function clearCache(): void
    {
        $enabledLocales = self::getEnabledLocales();
        foreach ($enabledLocales as $locale) {
            Cache::forget(self::CACHE_KEY_PREFIX.'_'.$locale);
        }
        Cache::forget(self::CACHE_KEY_PREFIX);
    }

    private static function getEnabledLocales(): array
    {
        try {
            $locales = array_keys(\App\Services\Core\LocaleService::getLocalesNames());

            return array_unique(array_map(function ($locale) {
                return str_contains($locale, '_') ? explode('_', $locale)[0] : $locale;
            }, $locales));
        } catch (\Exception $e) {
            return ['fr', 'en'];
        }
    }

    public function setCurrentRenderingSection(?Section $section): void
    {
        $this->currentRenderingSection = $section;
    }

    public function getCurrentRenderingSection(): ?Section
    {
        return $this->currentRenderingSection;
    }

    public function hasTheme(): bool
    {
        return $this->theme !== null && $this->theme->uuid !== 'default';
    }

    public function getTheme(): ExtensionThemeDTO
    {
        return $this->theme;
    }

    public function setTheme(string $theme, bool $save = false): void
    {
        $selectedTheme = collect($this->themes)->firstWhere('uuid', $theme);
        if ($selectedTheme === null) {
            throw new ThemeInvalidException("Theme [{$theme}] is not installed.");
        }

        $this->theme = $selectedTheme;
        if ($save) {
            Setting::updateSettings(['theme' => $theme]);
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

    /**
     * Get bottom menu items that act as footer columns (items with children).
     * Includes both dropdown items and legacy parent items that have children.
     */
    public function getFooterColumns(): Collection
    {
        return $this->getBottomLinks()->filter(function (MenuLink $item) {
            return $item->children && $item->children->isNotEmpty();
        });
    }

    /**
     * Get bottom menu items that act as inline footer links (leaf items, no children).
     */
    public function getFooterInlineLinks(): Collection
    {
        return $this->getBottomLinks()->filter(function (MenuLink $item) {
            return $item->link_type !== 'dropdown' && (! $item->children || $item->children->isEmpty());
        });
    }

    public function getCustomLinks(string $type): Collection
    {
        if (app()->environment('testing')) {
            return collect();
        }
        $support = $this->getTheme()->supportOption('menu_dropdown');
        $items = $this->menuLinks[$type] ??= MenuLink::query()
            ->with('children')
            ->where('type', $type)
            ->whereNull('parent_id')
            ->orderBy('position')
            ->get();

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

    public function getSetting()
    {
        $locale = app()->getLocale();
        $cacheKey = self::CACHE_KEY_PREFIX.'_'.$locale;

        return Cache::remember($cacheKey, 60 * 60 * 24 * 7, function () {
            $types = \App\Models\Personalization\MenuLink::pluck('type')->unique()->toArray();
            $links = collect($types)->mapWithKeys(function ($type) {
                return [$type.'_links' => MenuLink::where('type', $type)->whereNull('parent_id')->orderBy('position')->get()];
            });

            return $links->merge([
                'socials' => SocialNetwork::where('hidden', false)->orderBy('position')->get(),
                'sections_pages' => $this->getSectionsPages(),
                'sections' => Section::orderBy('order')->get(),
                'sections_html' => Section::orderBy('order')->get()->mapWithKeys(function (Section $section) {
                    $this->setCurrentRenderingSection($section);

                    return [$section->id => $section->toDTO()->render(false)];
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
        if ($this->theme === null) {
            $this->theme = $this->resolveCurrentTheme(
                $this->themes,
                \setting('theme'),
                $this->enabledThemeUuids(),
            );
        }
        $this->mergeWithExtensions();
    }

    /**
     * Resolve the active theme from the extension state first, then fall back to
     * the persisted setting. This prevents Default and another theme from being
     * considered active at the same time when both stores temporarily diverge.
     *
     * @param  array<int, ExtensionThemeDTO>  $themes
     * @param  array<int, string>  $enabledThemeUuids
     */
    private function resolveCurrentTheme(array $themes, ?string $configuredTheme, array $enabledThemeUuids): ExtensionThemeDTO
    {
        $availableThemes = collect($themes)->keyBy('uuid');
        $enabledThemes = collect($enabledThemeUuids)
            ->filter(fn (string $uuid) => $uuid !== 'default' && $availableThemes->has($uuid))
            ->unique()
            ->values();

        if ($enabledThemes->count() === 1) {
            return $availableThemes->get($enabledThemes->first());
        }

        if ($configuredTheme !== null && $availableThemes->has($configuredTheme)) {
            return $availableThemes->get($configuredTheme);
        }

        if ($enabledThemes->isNotEmpty()) {
            return $availableThemes->get($enabledThemes->first());
        }

        return $availableThemes->get('default') ?? $availableThemes->first();
    }

    /** @return array<int, string> */
    private function enabledThemeUuids(): array
    {
        try {
            return collect(ExtensionManager::readExtensionJson()['themes'] ?? [])
                ->filter(fn (array $theme) => ($theme['enabled'] ?? false) === true)
                ->pluck('uuid')
                ->filter(fn ($uuid) => is_string($uuid))
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
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
        if (! is_string($hexColor) || empty($hexColor)) {
            return 'black';
        }
        if (str_starts_with($hexColor, 'rgb')) {
            preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $hexColor, $matches);
            if (count($matches) === 4) {
                $r = $matches[1];
                $g = $matches[2];
                $b = $matches[3];
                $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

                return $luminance > 0.5 ? 'black' : 'white';
            }
        }
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
