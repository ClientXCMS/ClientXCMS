<?php

namespace App\Core\Domain;

use App\Contracts\Provisioning\ImportServiceInterface;
use App\Contracts\Provisioning\ServerTypeInterface;
use App\DTO\Provisioning\ConnectionResponse;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Models\Account\Customer;
use App\Models\Billing\ConfigOption;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Models\Store\Product;
use App\Services\Domain\DomainRegistrarManager;
use App\Services\Store\RecurringService;
use GuzzleHttp\Psr7\Response;

class DomainServerType implements ServerTypeInterface
{
    public function uuid(): string
    {
        return 'domain';
    }

    public function title(): string
    {
        return __('provisioning.domain_manager.server_type');
    }

    public function findServer(Product $product): ?Server
    {
        $serverId = $product->getMetadata('domain_server_id');
        if ($serverId) {
            return Server::where('type', $this->uuid())->find($serverId);
        }

        return Server::where('type', $this->uuid())->where('status', 'active')->first();
    }

    public function testConnection(array $params): ConnectionResponse
    {
        $registrar = app(DomainRegistrarManager::class)->get($params['hostname'] ?? $params['address'] ?? null);
        if ($registrar === null) {
            return new ConnectionResponse(new Response(404, [], 'Domain registrar not found'));
        }

        return $registrar->testConnection($params);
    }

    public function validate(): array
    {
        return [
            'hostname' => 'required|string',
            'address' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
        ];
    }

    public function createAccount(Service $service): ServiceStateChangeDTO
    {
        $registrar = app(DomainRegistrarManager::class)->fromService($service);
        if ($registrar === null) {
            return new ServiceStateChangeDTO($service, false, 'Domain registrar not found');
        }

        return $registrar->register($service);
    }

    public function suspendAccount(Service $service): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, true, 'Domain suspension is not supported');
    }

    public function unsuspendAccount(Service $service): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, true, 'Domain unsuspension is not supported');
    }

    public function expireAccount(Service $service): ServiceStateChangeDTO
    {
        $data = $service->data ?? [];
        $data['registrar_status'] = 'expired';
        $service->data = $data;

        return new ServiceStateChangeDTO($service, true, 'Domain expired');
    }

    public function onRenew(Service $service): ServiceStateChangeDTO
    {
        $registrar = app(DomainRegistrarManager::class)->fromService($service);
        if ($registrar === null) {
            return new ServiceStateChangeDTO($service, false, 'Domain registrar not found');
        }

        $months = app(RecurringService::class)->get($service->billing)['months'] ?? 12;
        $years = max(1, (int) ceil($months / 12));
        $result = $registrar->renew($service, $years);
        if ($result->success) {
            $data = $service->data ?? [];
            $data['expires_at'] = optional($service->expires_at)->toDateString();
            $service->data = $data;
            $service->save();
        }

        return $result;
    }

    public function changePassword(Service $service, ?string $password = null): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, false, 'Domain password change is not supported');
    }

    public function changeName(Service $service, ?string $name = null): ?ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, false, 'Domain name change is not supported');
    }

    public function importService(): ?ImportServiceInterface
    {
        return null;
    }

    public function upgradeService(Service $service, Product $product): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, false, 'Domain upgrade is not supported');
    }

    public function addOption(Service $service, ConfigOption $configOption): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, true, 'Domain option added');
    }

    public function changeCustomer(Service $service, Customer $customer): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, true, 'Domain customer changed locally');
    }

    public function getSupportedOptions(): array
    {
        return ['nameservers', 'dns'];
    }
}
