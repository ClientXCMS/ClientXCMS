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

namespace App\Events;

use App\Models\Provisioning\Service;

class GameHostingChangedEvent
{
    public Service $service;

    public string $action;

    public string $domain;

    protected string $name = 'gamehosting.event';

    public function __construct(Service $service, string $action, string $domain)
    {
        $this->service = $service;
        $this->action = $action;
        $this->domain = $domain;
    }
}
