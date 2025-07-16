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
namespace App\Events\Core\Service;

use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Models\Provisioning\Service;

class ServiceDeliveryFailed extends ServiceEvent
{
    public ServiceStateChangeDTO $dto;

    public function __construct(Service $invoice, ServiceStateChangeDTO $dto)
    {
        parent::__construct($invoice);
        $this->dto = $dto;
    }
}
