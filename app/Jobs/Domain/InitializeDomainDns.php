<?php

namespace App\Jobs\Domain;

use App\Models\Provisioning\Service;
use App\Services\Domain\DomainDnsInitializer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class InitializeDomainDns implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public int $timeout = 240;

    public function __construct(public int $serviceId) {}

    public function handle(DomainDnsInitializer $initializer): void
    {
        $service = Service::find($this->serviceId);
        if ($service && $service->type === 'domain') {
            $initializer->initialize($service);
        }
    }
}
