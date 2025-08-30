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


namespace App\Extensions;

use App\Contracts\Store\ProductTypeInterface;
use App\Core\Admin\Dashboard\AdminCardWidget;
use App\Core\Admin\Dashboard\AdminCountWidget;
use App\Core\Menu\AdminMenuItem;
use App\Core\Menu\ClientMenuItem;
use App\Core\Menu\FrontMenuItem;
use Illuminate\Support\Collection;

class ExtensionCollectionsManager
{
    private Collection $productTypes;

    private Collection $adminMenuItems;

    private Collection $frontMenuItems;

    private Collection $clientMenuItems;

    public Collection $componants;

    public Collection $modules;

    public Collection $addons;

    public Collection $themes;

    public Collection $adminCountWidgets;

    public Collection $adminCardsWidgets;

    public Collection $sectionsContexts;

    public Collection $seeders;

    public Collection $protectedRoutes;

    public function __construct()
    {
        $this->productTypes = collect();
        $this->adminMenuItems = collect();
        $this->frontMenuItems = collect();
        $this->clientMenuItems = collect();
        $this->componants = collect();
        $this->modules = collect();
        $this->addons = collect();
        $this->themes = collect();
        $this->adminCountWidgets = collect();
        $this->adminCardsWidgets = collect();
        $this->sectionsContexts = collect();
        $this->seeders = collect();
        $this->protectedRoutes = collect();
    }

    public function addProductType(ProductTypeInterface $productType): void
    {
        $this->productTypes = $this->productTypes->merge([$productType->uuid() => $productType]);
    }

    public function getProductTypes(): Collection
    {
        return $this->productTypes;
    }

    public function addAdminMenuItem(AdminMenuItem $adminMenuItem): void
    {
        $this->adminMenuItems = $this->adminMenuItems->merge([$adminMenuItem->uuid => $adminMenuItem]);
    }

    public function getAdminMenuItems(): Collection
    {
        return collect(\Cache::get('adminMenuItems', $this->adminMenuItems))->sortBy((fn ($item) => $item->position));
    }

    public function addFrontMenuItem(FrontMenuItem $frontMenuItem): void
    {
        $this->frontMenuItems = $this->frontMenuItems->merge([$frontMenuItem->uuid => $frontMenuItem]);
    }

    public function getFrontMenuItems(): Collection
    {
        return collect(\Cache::get('frontMenuItems', $this->frontMenuItems))->sortBy((fn ($item) => $item->position));
    }

    public function addClientMenuItem(ClientMenuItem $clientMenuItem): void
    {
        $this->clientMenuItems = $this->clientMenuItems->merge([$clientMenuItem->uuid => $clientMenuItem]);
    }

    public function getClientMenuItems(): Collection
    {
        return $this->clientMenuItems;
    }

    public function addAdminCountWidget(AdminCountWidget $adminCountWidget): void
    {
        $this->adminCountWidgets = $this->adminCountWidgets->merge([$adminCountWidget->uuid => $adminCountWidget]);
    }

    public function getAdminCountWidgets(): Collection
    {
        return \Cache::get('adminCountWidgets', $this->adminCountWidgets);
    }

    public function addAdminCardsWidget(AdminCardWidget $adminCardsWidget): void
    {
        $this->adminCardsWidgets = $this->adminCardsWidgets->merge([$adminCardsWidget->uuid => $adminCardsWidget]);
    }

    public function getAdminCardsWidgets(): Collection
    {
        return \Cache::get('adminCardsWidgets', $this->adminCardsWidgets);
    }

    public function addSectionContext(string $uuid, callable $function): void
    {
        $params = \Cache::get('section_context_'.$uuid, call_user_func($function));
        $this->sectionsContexts = $this->sectionsContexts->merge([$uuid => $params]);
    }

    public function getSectionsContexts(): Collection
    {
        return $this->sectionsContexts;
    }

    public function addSeeder(string $class): void
    {
        $this->seeders = $this->seeders->merge([$class]);
    }

    public function addSeeders(array $classes): void
    {
        $this->seeders = $this->seeders->merge($classes);
    }

    public function getSeeders(): Collection
    {
        return $this->seeders;
    }

    public function addProtectedRoute(string $url): void
    {
        $this->protectedRoutes = $this->protectedRoutes->merge([$url]);
    }

    public function getProtectedRoutes(): Collection
    {
        return $this->protectedRoutes;
    }
}
