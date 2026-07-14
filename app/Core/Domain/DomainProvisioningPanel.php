<?php

namespace App\Core\Domain;

use App\Contracts\Provisioning\CardAdminServiceInterface;
use App\Contracts\Provisioning\PanelProvisioningInterface;
use App\DTO\Provisioning\ProvisioningTabDTO;
use App\Models\Provisioning\Service;
use App\Services\Domain\DomainRegistrarManager;

class DomainProvisioningPanel implements PanelProvisioningInterface
{
    public function uuid(): string
    {
        return 'domain';
    }

    public function tabs(Service $service): array
    {
        if (! config('features.domain_management')) {
            return [];
        }

        return [
            new ProvisioningTabDTO([
                'uuid' => 'nameservers',
                'active' => true,
                'title' => __('provisioning.domain_manager.nameservers'),
                'permission' => 'service.show',
                'icon' => '<i class="bi bi-diagram-3"></i>',
            ]),
            new ProvisioningTabDTO([
                'uuid' => 'dns',
                'active' => true,
                'title' => __('provisioning.domain_manager.dns'),
                'permission' => 'service.show',
                'icon' => '<i class="bi bi-globe2"></i>',
            ]),
        ];
    }

    public function render(Service $service, array $permissions = [])
    {
        $registrar = app(DomainRegistrarManager::class)->fromService($service);
        $domain = $registrar?->getDomain($service);

        return view('front.provisioning.domains.show', compact('service', 'domain'));
    }

    public function renderAdmin(Service $service)
    {
        return $this->render($service);
    }

    public function permissions(): array
    {
        return ['service.show'];
    }

    public function cardAdmin(Service $service): ?CardAdminServiceInterface
    {
        return null;
    }

    public function getTab(Service $service, string $uuid): ?ProvisioningTabDTO
    {
        return collect($this->tabs($service))->firstWhere('uuid', $uuid);
    }

    public function renderNameservers(Service $service)
    {
        $nameservers = app(DomainRegistrarManager::class)->fromService($service)?->getNameservers($service) ?? [];

        return view('front.provisioning.domains.nameservers', compact('service', 'nameservers'));
    }

    public function renderDns(Service $service)
    {
        $records = app(DomainRegistrarManager::class)->fromService($service)?->getDnsRecords($service) ?? [];

        return view('front.provisioning.domains.dns', compact('service', 'records'));
    }
}
