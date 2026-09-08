<?php

namespace App\Services\Domain;

use App\Contracts\Domain\DomainDnsInitializationInterface;
use App\Models\Provisioning\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DomainDefaultsService
{
    public const FIELDS = ['default_nameservers', 'default_dns_records', 'apply_default_dns', 'dns_management', 'whois_privacy', 'server_id'];

    public const TYPES = ['A', 'AAAA', 'CNAME', 'MX', 'TXT'];

    public function validate(array $data, ?Server $server = null): array
    {
        $data['default_nameservers'] = array_values(array_filter(array_map(fn ($name) => strtolower(rtrim(trim((string) $name), '.')), $data['default_nameservers'] ?? [])));
        $data['default_dns_records'] = array_values($data['default_dns_records'] ?? []);
        Validator::make($data, [
            'default_nameservers' => 'array|max:8',
            'default_nameservers.*' => ['required', 'distinct', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/'],
            'default_dns_records' => 'array|max:100',
            'default_dns_records.*.type' => 'required|in:A,AAAA,CNAME,MX,TXT',
            'default_dns_records.*.name' => ['required', 'string', 'max:253', 'regex:/^(?:@|\*|(?:\*\.)?[a-zA-Z0-9_](?:[a-zA-Z0-9_.-]*[a-zA-Z0-9_])?)$/'],
            'default_dns_records.*.value' => 'required|string|max:4096',
            'default_dns_records.*.ttl' => 'required|integer|min:60|max:2147483647',
            'default_dns_records.*.priority' => 'nullable|integer|min:0|max:65535',
        ])->validate();
        if (count($data['default_nameservers']) === 1) {
            throw ValidationException::withMessages(['default_nameservers' => __('provisioning.admin.domain_tlds.tools.invalid_dns')]);
        }
        $byName = [];
        foreach ($data['default_dns_records'] as $i => &$record) {
            $record = array_intersect_key($record, array_flip(['type', 'name', 'value', 'ttl', 'priority']));
            $record['name'] = strtolower(rtrim($record['name'], '.'));
            $record['ttl'] = (int) $record['ttl'];
            $rules = match ($record['type']) {
                'A' => ['ipv4'], 'AAAA' => ['ipv6'],
                'MX', 'CNAME' => ['max:253', 'regex:/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z0-9-]+\.?$/'],
                default => [],
            };
            Validator::make(['value' => $record['value']], ['value' => $rules])->validate();
            if ($record['type'] === 'MX') {
                Validator::make($record, ['priority' => 'required|integer|min:0|max:65535'])->validate();
                $record['priority'] = (int) $record['priority'];
            } else {
                unset($record['priority']);
            }
            if ($record['type'] === 'CNAME' && $record['name'] === '@') {
                throw ValidationException::withMessages(["default_dns_records.$i.name" => __('provisioning.admin.domain_tlds.tools.invalid_dns')]);
            }
            $byName[$record['name']][] = $record['type'];
        }
        unset($record);
        foreach ($byName as $types) {
            if (in_array('CNAME', $types, true) && count($types) > 1) {
                throw ValidationException::withMessages(['default_dns_records' => __('provisioning.admin.domain_tlds.tools.invalid_dns')]);
            }
        }
        if (! empty($data['apply_default_dns'])) {
            $registrar = $server ? app(DomainRegistrarManager::class)->all()->get($server->hostname) : null;
            if (! $data['dns_management'] || ! $registrar instanceof DomainDnsInitializationInterface || count($data['default_nameservers']) < 2 || $data['default_dns_records'] === []) {
                throw ValidationException::withMessages(['apply_default_dns' => __('provisioning.admin.domain_tlds.tools.incompatible_dns')]);
            }
            $capabilities = $registrar->dnsCapabilities($server);
            if (array_diff($data['default_nameservers'], $capabilities['nameservers']) || array_diff(array_column($data['default_dns_records'], 'type'), $capabilities['types'])) {
                throw ValidationException::withMessages(['apply_default_dns' => __('provisioning.admin.domain_tlds.tools.incompatible_dns')]);
            }
        }

        return $data;
    }
}
