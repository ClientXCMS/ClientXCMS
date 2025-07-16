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
        // if (Section::count() == 0) {
        Section::scanSections();
        // }
        ThemeManager::clearCache();
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
