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

namespace App\Core;

use App\Abstracts\AbstractServerType;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Models\Provisioning\Service;
use App\Models\Store\Product;
use GuzzleHttp\Psr7\Response;

class NoneServerType extends AbstractServerType
{
    /**
     * Renvoie toujours null
     */
    public function findServer(Product $product): ?\App\Models\Provisioning\Server
    {
        return null;
    }

    public function createAccount(\App\Models\Provisioning\Service $service): \App\DTO\Provisioning\ServiceStateChangeDTO
    {
        return new \App\DTO\Provisioning\ServiceStateChangeDTO($service, true, 'Created account on none server type');
    }

    public function testConnection(array $params): \App\DTO\Provisioning\ConnectionResponse
    {
        $response = new Response(200, [], 'Test connection on none server type');

        return new \App\DTO\Provisioning\ConnectionResponse($response, 'Test connection on none server type');
    }

    public function suspendAccount(Service $service): \App\DTO\Provisioning\ServiceStateChangeDTO
    {
        return new \App\DTO\Provisioning\ServiceStateChangeDTO($service, true, 'Suspended account on none server type');
    }

    public function unsuspendAccount(Service $service): \App\DTO\Provisioning\ServiceStateChangeDTO
    {
        return new \App\DTO\Provisioning\ServiceStateChangeDTO($service, true, 'Unsuspended account on none server type');
    }

    public function expireAccount(Service $service): \App\DTO\Provisioning\ServiceStateChangeDTO
    {
        return new \App\DTO\Provisioning\ServiceStateChangeDTO($service, true, 'Deleted account on none server type');
    }

    public function onRenew(Service $service): \App\DTO\Provisioning\ServiceStateChangeDTO
    {
        return new \App\DTO\Provisioning\ServiceStateChangeDTO($service, true, 'Renewed account on none server type');
    }

    public function upgradeService(Service $service, Product $product): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, true, 'Upgraded service on none server type');
    }
}
