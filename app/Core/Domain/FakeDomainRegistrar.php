<?php

namespace App\Core\Domain;

use App\Contracts\Domain\DomainRegistrarInterface;
use App\DTO\Domain\DomainAvailabilityDTO;
use App\DTO\Domain\DomainInfoDTO;
use App\DTO\Provisioning\ConnectionResponse;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Models\Provisioning\Service;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;

class FakeDomainRegistrar implements DomainRegistrarInterface
{
    public function uuid(): string
    {
        return 'fake';
    }

    public function title(): string
    {
        return 'Fake registrar';
    }

    public function validate(): array
    {
        return [
            'hostname' => 'nullable|string',
            'address' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
        ];
    }

    public function testConnection(array $params): ConnectionResponse
    {
        return new ConnectionResponse(new Response(200, [], 'Fake registrar ready'));
    }

    public function checkAvailability(string $domain): DomainAvailabilityDTO
    {
        $blocked = str_contains($domain, 'taken') || str_contains($domain, 'unavailable');

        return new DomainAvailabilityDTO($domain, ! $blocked, $blocked ? 'Domain unavailable' : null);
    }

    public function register(Service $service): ServiceStateChangeDTO
    {
        $data = $service->data ?? [];
        $data['registrar_id'] = $data['registrar_id'] ?? 'fake-' . sha1($data['domain'] ?? $service->uuid);
        $data['registrar_status'] = 'active';
        $data['created_at'] = $data['created_at'] ?? now()->toDateString();
        $data['expires_at'] = optional($service->expires_at)->toDateString();
        $data['nameservers'] = $data['nameservers'] ?? ['ns1.example.net', 'ns2.example.net'];
        $data['dns_records'] = $data['dns_records'] ?? [];
        $service->data = $data;
        $service->save();

        return new ServiceStateChangeDTO($service, true, 'Domain registered');
    }

    public function renew(Service $service, int $years): ServiceStateChangeDTO
    {
        $data = $service->data ?? [];
        $data['expires_at'] = optional($service->expires_at)->toDateString();
        $service->data = $data;
        $service->save();

        return new ServiceStateChangeDTO($service, true, 'Domain renewed');
    }

    public function getDomain(Service $service): DomainInfoDTO
    {
        $data = $service->data ?? [];

        return new DomainInfoDTO(
            $data['domain'] ?? $service->name,
            $data['registrar_status'] ?? $service->status,
            isset($data['created_at']) ? Carbon::parse($data['created_at']) : $service->created_at,
            isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : $service->expires_at,
            $data['nameservers'] ?? [],
            $data['registrar_id'] ?? null,
        );
    }

    public function getNameservers(Service $service): array
    {
        return $service->data['nameservers'] ?? ['ns1.example.net', 'ns2.example.net'];
    }

    public function updateNameservers(Service $service, array $nameservers): ServiceStateChangeDTO
    {
        $data = $service->data ?? [];
        $data['nameservers'] = array_values(array_filter($nameservers));
        $service->data = $data;
        $service->save();

        return new ServiceStateChangeDTO($service, true, 'Nameservers updated');
    }

    public function getDnsRecords(Service $service): array
    {
        return $service->data['dns_records'] ?? [];
    }

    public function createDnsRecord(Service $service, array $record): ServiceStateChangeDTO
    {
        $data = $service->data ?? [];
        $records = $data['dns_records'] ?? [];
        $record['id'] = $record['id'] ?? uniqid('dns_', false);
        $records[] = $record;
        $data['dns_records'] = $records;
        $service->data = $data;
        $service->save();

        return new ServiceStateChangeDTO($service, true, 'DNS record created');
    }

    public function updateDnsRecord(Service $service, string $recordId, array $record): ServiceStateChangeDTO
    {
        $data = $service->data ?? [];
        $records = collect($data['dns_records'] ?? [])->map(function ($item) use ($recordId, $record) {
            return ($item['id'] ?? null) === $recordId ? array_merge($item, $record, ['id' => $recordId]) : $item;
        })->values()->toArray();
        $data['dns_records'] = $records;
        $service->data = $data;
        $service->save();

        return new ServiceStateChangeDTO($service, true, 'DNS record updated');
    }

    public function deleteDnsRecord(Service $service, string $recordId): ServiceStateChangeDTO
    {
        $data = $service->data ?? [];
        $data['dns_records'] = collect($data['dns_records'] ?? [])->reject(fn ($item) => ($item['id'] ?? null) === $recordId)->values()->toArray();
        $service->data = $data;
        $service->save();

        return new ServiceStateChangeDTO($service, true, 'DNS record deleted');
    }
}
