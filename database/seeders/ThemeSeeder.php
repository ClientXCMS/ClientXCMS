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

    private function seedMenus()
    {
        $themes = app('theme')->getThemes();
        foreach ($themes as $theme) {
            $path = $theme->path.'/menus.json';
            if (file_exists($path)) {
                $menus = json_decode(file_get_contents($path), true);
                if ($menus === null) {
                    logger()->info('[ThemeSeeder] Unable to parse menus.json for theme '.$theme->name);
                    continue;
                }
                if (is_array($menus)) {
                    foreach ($menus as $type => $menuList) {
                        foreach ($menuList as $menu) {
                            if (MenuLink::where('type', $type)->where('name', $menu['name'])->exists()) {
                                continue;
                            }
                            $created = MenuLink::create([
                                'name' => $menu['name'],
                                'url' => $menu['url'] ?? "#",
                                'icon' => $menu['icon'] ?? null,
                                'type' => $type,
                                'position' => $menu['position'] ?? 0,
                            ]);
                            if (array_key_exists('metadata', $menu) && is_array($menu['metadata'])) {
                                foreach ($menu['metadata'] as $key => $value) {
                                    $created->attachMetadata($key, $value);
                                }
                            }
                        }
                    }
                }
            }
        }
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
