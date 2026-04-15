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

namespace App\Traits\Controllers;

use App\Models\Provisioning\Server;
use App\Models\Store\Product;

trait ServiceControllerTrait
{
    private function getProductsList()
    {
        $products = Product::getAllProducts(true);
        $products->put('none', __('global.none'));

        return $products;
    }

    private function getServersList(string $type)
    {
        if ($type == 'none') {
            $servers = Server::getAvailable(true)->get()->pluck('name', 'id');
        } elseif ($type == 'pterobox') {
            $servers = Server::all()->whereIn('type', ['pterobox', 'wisp', 'pterodactyl'])->pluck('name', 'id');

        } else {
            $servers = Server::all()->where('type', $type)->pluck('name', 'id');
        }
        $servers->put('none', __('global.none'));

        return $servers;
    }

    private function getProductTypes()
    {
        return app('extension')->getProductTypes()->keys()->merge(['none'])->mapWithKeys(function ($k) {
            return [$k => $k];
        });
    }
}
