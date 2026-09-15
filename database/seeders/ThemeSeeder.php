<?php

namespace Database\Seeders;

use App\Models\Personalization\MenuLink;
use App\Models\Personalization\Section;
use App\Models\Personalization\SocialNetwork;
use App\Theme\ThemeManager;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
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
            if (! $theme->supportsMenus()) {
                continue;
            }

            $path = $theme->path.'/menus.json';
            $menus = $theme->menus();

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
                        'badge' => $menu['badge'] ?? null,
                        'description' => $menu['description'] ?? null,
                        'link_type' => $menu['link_type'] ?? 'link',
                        'allowed_role' => $menu['allowed_role'] ?? 'all',
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

    private function isValidMenu(mixed $menu): bool
    {
        if (! is_array($menu) || ! isset($menu['name']) || ! is_string($menu['name']) || trim($menu['name']) === '' || strlen($menu['name']) > 255) {
            return false;
        }

        foreach (['url', 'link', 'icon', 'badge', 'description'] as $key) {
            if (array_key_exists($key, $menu) && $menu[$key] !== null && (! is_string($menu[$key]) || strlen($menu[$key]) > 255)) {
                return false;
            }
        }

        if (isset($menu['link_type']) && ! in_array($menu['link_type'], ['link', 'new_tab', 'dropdown'], true)) {
            return false;
        }

        if (isset($menu['allowed_role']) && ! in_array($menu['allowed_role'], ['all', 'staff', 'customer', 'logged'], true)) {
            return false;
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
