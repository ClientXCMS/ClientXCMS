<?php

namespace App\Core\Domain\Concerns;

use App\Models\Provisioning\Service;
use App\Models\Store\DomainTld;
use App\Services\Store\RecurringService;
use RuntimeException;

trait ReadsDomainService
{
    protected function domain(Service $service): string
    {
        return $this->normalizeDomain((string) (($service->data ?? [])['domain'] ?? $service->name));
    }

    protected function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain, " .\t\n\r\0\x0B"));
        $this->splitDomain($domain);

        return $domain;
    }

    /** @return array{0: string, 1: string} The label and the extension without its leading dot. */
    protected function splitDomain(string $domain): array
    {
        $position = strrpos($domain, '.');
        if ($position === false || $position === 0 || $position === strlen($domain) - 1) {
            throw new RuntimeException('Invalid domain name');
        }

        return [substr($domain, 0, $position), substr($domain, $position + 1)];
    }

    /** The stored tld wins so multi-level extensions (co.uk) resolve to their configured TLD. */
    protected function extension(Service $service): string
    {
        $extension = trim((string) (($service->data ?? [])['tld'] ?? ''));
        if ($extension !== '') {
            return strtolower(ltrim($extension, '.'));
        }

        return $this->splitDomain($this->domain($service))[1];
    }

    protected function tld(Service $service): ?DomainTld
    {
        return DomainTld::where('extension', '.'.$this->extension($service))->first();
    }

    protected function dnsManagement(Service $service): bool
    {
        return (bool) (($service->data ?? [])['dns_management'] ?? $this->tld($service)?->dns_management ?? false);
    }

    protected function whoisPrivacy(Service $service): bool
    {
        return (bool) (($service->data ?? [])['whois_privacy'] ?? $this->tld($service)?->whois_privacy ?? false);
    }

    /** Registrars bill whole years: partial years are rounded up and capped to the 1-10 registry range. */
    protected function years(Service $service): int
    {
        $months = app(RecurringService::class)->get($service->billing)['months'] ?? 12;

        return max(1, min(10, (int) ceil($months / 12)));
    }
}
