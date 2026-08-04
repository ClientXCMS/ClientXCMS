<?php

namespace Database\Seeders;

use App\Models\Personalization\MenuLink;
use App\Models\Personalization\Section;
use App\Models\Personalization\SocialNetwork;
use App\Theme\ThemeManager;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    private const MAX_MAPPING_FILE_SIZE = 1_048_576;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (SocialNetwork::count() == 0) {

            $this->createSocialNetwork('bi bi-twitter-x', 'Twitter', 'https://twitter.com/ClientXCMS');
            $this->createSocialNetwork('bi bi-facebook', 'Facebook', 'https://www.facebook.com/ClientXCMS');
            $this->createSocialNetwork('bi bi-instagram', 'Instagram', 'https://www.instagram.com/ClientXCMS');
            $this->createSocialNetwork('bi bi-twitch', 'Twitch', 'https://www.twitch.tv/ClientXCMS');
            $this->createSocialNetwork('bi bi-discord', 'Discord', 'https://discord.gg/ClientXCMS');
            $this->createSocialNetwork('bi bi-linkedin', 'Linkedin', 'https://www.linkedin.com/company/ClientXCMS');
        }
        if (MenuLink::where('type', 'bottom')->count() == 0) {
            MenuLink::newBottonMenu();
        }

        if (MenuLink::where('type', 'front')->count() == 0) {
            MenuLink::newFrontMenu();
        }
        $this->seedMenus();
        // if (Section::count() == 0) {
        Section::scanSections();
        // }
        ThemeManager::clearCache();

    }

    private function seedMenus(): void
    {
        $themes = app('theme')->getThemes();
        foreach ($themes as $theme) {
            $path = $theme->path.'/menus.json';
            if (! is_file($path)) {
                continue;
            }

            $menus = $this->readMappingFile($path, $theme->name);
            if ($menus === null || array_is_list($menus)) {
                logger()->warning('[ThemeSeeder] menus.json must contain an object indexed by menu type.', [
                    'path' => $path,
                    'theme' => $theme->name,
                ]);

                continue;
            }

            foreach ($menus as $type => $menuList) {
                if (! is_string($type) || preg_match('/^[A-Za-z0-9_-]{1,64}$/', $type) !== 1 || ! is_array($menuList) || ! array_is_list($menuList)) {
                    $this->logInvalidMapping($path, $theme->name, (string) $type, 'invalid menu type or menu list');

                    continue;
                }

                foreach ($menuList as $index => $menu) {
                    if (! $this->isValidMenu($menu)) {
                        $this->logInvalidMapping($path, $theme->name, $type.'.'.$index, 'invalid menu entry');

                        continue;
                    }

                    if (MenuLink::where('type', $type)->where('name', $menu['name'])->exists()) {
                        continue;
                    }

                    $created = MenuLink::create([
                        'name' => $menu['name'],
                        'url' => $menu['url'] ?? $menu['link'] ?? '#',
                        'icon' => $menu['icon'] ?? null,
                        'type' => $type,
                        'position' => $menu['position'] ?? 0,
                    ]);

                    foreach ($menu['metadata'] ?? [] as $key => $value) {
                        if (! is_string($key) || $key === '' || strlen($key) > 255 || (! is_scalar($value) && $value !== null)) {
                            $this->logInvalidMapping($path, $theme->name, $type.'.'.$index.'.metadata', 'invalid metadata entry');

                            continue;
                        }

                        $created->attachMetadata($key, $value);
                    }
                }
            }
        }
    }

    private function readMappingFile(string $path, string $theme): ?array
    {
        if (! is_readable($path)) {
            logger()->warning('[ThemeSeeder] Unable to safely read menus.json.', compact('path', 'theme'));

            return null;
        }

        $size = filesize($path);
        if ($size === false || $size > self::MAX_MAPPING_FILE_SIZE) {
            logger()->warning('[ThemeSeeder] Unable to safely read menus.json.', compact('path', 'theme', 'size'));

            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            logger()->warning('[ThemeSeeder] Unable to read menus.json.', compact('path', 'theme'));

            return null;
        }

        try {
            $menus = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            logger()->warning('[ThemeSeeder] Unable to parse menus.json.', [
                'path' => $path,
                'theme' => $theme,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        return is_array($menus) ? $menus : null;
    }

    private function isValidMenu(mixed $menu): bool
    {
        if (! is_array($menu) || ! isset($menu['name']) || ! is_string($menu['name']) || trim($menu['name']) === '' || strlen($menu['name']) > 255) {
            return false;
        }

        foreach (['url', 'link', 'icon'] as $key) {
            if (array_key_exists($key, $menu) && $menu[$key] !== null && (! is_string($menu[$key]) || strlen($menu[$key]) > 255)) {
                return false;
            }
        }

        if (array_key_exists('position', $menu) && (! is_int($menu['position']) || $menu['position'] < 0)) {
            return false;
        }

        return ! array_key_exists('metadata', $menu) || is_array($menu['metadata']);
    }

    private function logInvalidMapping(string $path, string $theme, string $entry, string $reason): void
    {
        logger()->warning('[ThemeSeeder] Invalid menus.json mapping skipped.', compact('path', 'theme', 'entry', 'reason'));
    }

    private function createSocialNetwork(string $icon, string $name, string $url): void
    {
        SocialNetwork::insert([
            'icon' => $icon,
            'name' => $name,
            'url' => $url,
        ]);
    }
}
