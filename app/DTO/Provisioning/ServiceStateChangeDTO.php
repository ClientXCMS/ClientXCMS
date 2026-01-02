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

namespace App\DTO\Provisioning;

use App\Models\Provisioning\Service;
use Carbon\Carbon;

class ServiceStateChangeDTO
{
    public Service $service;

    public bool $success;

    public string $message;

    public Carbon $created_at;

    public array $data = [];

    public function __construct(Service $service, bool $success, string $message, array $data = [])
    {
        $this->service = $service;
        $this->success = $success;
        $this->message = $message;
        $this->created_at = Carbon::now();
        $this->data = $data;
    }
}
