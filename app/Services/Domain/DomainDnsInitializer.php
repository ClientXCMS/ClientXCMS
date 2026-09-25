<?php

namespace App\Services\Domain;

use App\Contracts\Domain\DomainDnsInitializationInterface;
use App\Models\Provisioning\Service;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class DomainDnsInitializer
{
    public function initialize(Service $service): void
    {
        Cache::lock('domain-dns:'.$service->id, 300)->block(1, function () use ($service) {
            $service->refresh();
            $data = $service->data ?? [];
            if (empty($data['apply_default_dns']) || ($data['dns_initialization']['status'] ?? '') === 'completed') {
                return;
            }
            try {
                $registrar = app(DomainRegistrarManager::class)->fromService($service);
                if (! $registrar instanceof DomainDnsInitializationInterface || ! $service->server || empty($data['registrar_id'])) {
                    throw new RuntimeException('DNS initialization is not supported or domain is not registered');
                }
                $domain = $registrar->getDomain($service);
                if (! in_array(strtolower($domain->status), ['active', 'act'], true)) {
                    throw new RuntimeException('Domain registration is still pending');
                }
                app(DomainDefaultsService::class)->validate([
                    'default_nameservers' => $registrar->getNameservers($service),
                    'default_dns_records' => $data['default_dns_records'] ?? [],
                    'apply_default_dns' => true,
                    'dns_management' => $data['dns_management'] ?? false,
                ], $service->server);
                $existing = $registrar->initializationRecords($service);
                foreach ($data['default_dns_records'] ?? [] as $record) {
                    $name = $this->relativeName($record['name'], $domain->domain);
                    $atName = array_filter($existing, fn ($item) => $this->relativeName($item['name'] ?? '@', $domain->domain) === $name);
                    $sameType = array_filter($atName, fn ($item) => strtoupper($item['type']) === $record['type']);
                    $exact = array_filter($sameType, fn ($item) => $this->sameValue($item, $record));
                    if ($exact) {
                        continue;
                    }
                    if ($sameType || ($atName && ($record['type'] === 'CNAME' || in_array('CNAME', array_column($atName, 'type'), true)))) {
                        throw new RuntimeException('Existing DNS record conflicts with template: '.$record['name'].' '.$record['type']);
                    }
                    $result = $registrar->createDnsRecord($service, $record);
                    if (! $result->success) {
                        throw new RuntimeException($result->message);
                    }
                    $existing[] = $record;
                }
                $state = ['status' => 'completed', 'updated_at' => now()->toIso8601String()];
            } catch (Throwable $e) {
                $state = ['status' => 'failed', 'error' => $e->getMessage(), 'updated_at' => now()->toIso8601String()];
            }
            $service->refresh();
            $service->data = array_merge($service->data ?? [], ['dns_initialization' => $state]);
            $service->save();
        });
    }

    private function relativeName(string $name, string $domain): string
    {
        $name = strtolower(rtrim($name, '.'));
        $domain = strtolower(rtrim($domain, '.'));
        if ($name === '' || $name === '@' || $name === $domain) {
            return '@';
        }

        return str_ends_with($name, '.'.$domain) ? substr($name, 0, -strlen($domain) - 1) : $name;
    }

    private function sameValue(array $left, array $right): bool
    {
        $normalize = fn ($value) => in_array($right['type'], ['CNAME', 'MX'], true) ? strtolower(rtrim($value, '.')) : $value;

        return $normalize($left['value'] ?? '') === $normalize($right['value']) && (int) ($left['priority'] ?? $left['prio'] ?? 0) === (int) ($right['priority'] ?? 0);
    }
}
