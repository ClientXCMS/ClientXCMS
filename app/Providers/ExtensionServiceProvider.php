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
namespace App\Providers;

use App\Extensions\AddonManager;
use App\Extensions\ExtensionManager;
use App\Extensions\ModuleManager;
use App\Helpers\Countries;
use App\Http\Controllers\Admin\Personalization\EmailTemplateController;
use App\Http\Controllers\Admin\Personalization\SectionController;
use App\Http\Controllers\Admin\Personalization\SettingsPersonalizationController;
use App\Http\Controllers\Admin\Personalization\SocialCrudController;
use App\Http\Controllers\Admin\Personalization\ThemeController;
use App\Models\Admin\Permission;
use App\Models\Store\Group;
use App\Theme\ThemeManager;
use Illuminate\Support\ServiceProvider;

class ExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('module', ModuleManager::class);
        $this->app->singleton('extension', ExtensionManager::class);
        $this->app->singleton('addon', AddonManager::class);
        $this->app->singleton('theme', ThemeManager::class);
        $this->app->make(ExtensionManager::class)->autoload($this->app);
        $service = $this->app->make('settings');
        $service->addCard('personalization', 'personalization.title', 'personalization.description', 10);
        $service->addCardItem('personalization', 'theme', 'personalization.theme.title', 'personalization.theme.description', 'bi bi-brush', [ThemeController::class, 'showTheme'], Permission::MANAGE_PERSONALIZATION);
        $service->addCardItem('personalization', 'home', 'personalization.home.title', 'personalization.home.description', 'bi bi-house', [SettingsPersonalizationController::class, 'showHomeSettings'], Permission::MANAGE_SETTINGS);

        $service->addCardItem('personalization', 'social', 'personalization.social.title', 'personalization.social.description', 'bi bi-easel', [SocialCrudController::class, 'index'], Permission::MANAGE_PERSONALIZATION);
        $service->addCardItem('personalization', 'front_menu', 'personalization.front_menu.title', 'personalization.front_menu.description', 'bi bi-menu-down', [SettingsPersonalizationController::class, 'showFrontMenu'], Permission::MANAGE_PERSONALIZATION);
        $service->addCardItem('personalization', 'bottom_menu', 'personalization.bottom_menu.title', 'personalization.bottom_menu.description', 'bi bi-menu-up', [SettingsPersonalizationController::class, 'showBottomMenu'], Permission::MANAGE_PERSONALIZATION);
        $service->addCardItem('personalization', 'primary', 'personalization.primary.title', 'personalization.primary.description', 'bi bi-paint-bucket', [SettingsPersonalizationController::class, 'showPrimaryColors'], Permission::MANAGE_PERSONALIZATION);
        $service->addCardItem('personalization', 'seo', 'personalization.seo.title', 'personalization.seo.description', 'bi bi-browser-chrome', [SettingsPersonalizationController::class, 'showSeoSettings'], Permission::MANAGE_SETTINGS);
        $service->addCardItem('personalization', 'sections', 'personalization.sections.title', 'personalization.sections.description', 'bi bi-layout-text-sidebar', [SectionController::class, 'index'], Permission::MANAGE_SETTINGS);
        // $service->addCardItem('personalization','announcement','personalization.announcement.title','personalization.announcement.description','bi bi-megaphone',[SettingsPersonalizationController::class,'showAnnouncement'],Permission::MANAGE_SETTINGS);
        $service->addCardItem('personalization', 'email_templates', 'personalization.email_templates.title', 'personalization.email_templates.description', 'bi bi-envelope', [EmailTemplateController::class, 'index'], Permission::MANAGE_PERSONALIZATION);
    }

    public function boot(): void
    {
        $extension = $this->app->make('extension');
        $extension->addSectionContext('store_groups', function () {
            if (! is_installed() || app()->runningUnitTests()) {
                return ['groups' => []];
            }
            try {
                return [
                    'groups' => Group::getAvailable()->whereNull('parent_id')->orderBy('sort_order')->orderBy('pinned')->get(),
                ];
            } catch (\Exception $e) {
                return ['groups' => []];
            }
        });
        $extension->addSectionContext('register_cta', function () {
            return [
                'countries' => Countries::names(),
            ];
        });
    }
}
