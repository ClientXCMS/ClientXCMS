<?php

namespace Tests\Unit;

use App\Abstracts\AbstractDomainRegistrar;
use App\Core\Domain\FakeDomainRegistrar;
use App\DTO\Domain\DomainAvailabilityDTO;
use App\DTO\Provisioning\ConnectionResponse;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Models\Provisioning\Service;
use App\Services\Domain\DomainCatalogService;
use App\Services\Domain\DomainDefaultsService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DomainRegistrarInfrastructureTest extends TestCase
{
    public function test_batch_availability_falls_back_to_single_checks_for_existing_registrars(): void
    {
        $registrar = new MinimalDomainRegistrar;
        $results = $registrar->checkAvailabilityBatch(['example.com', 'example.net']);

        $this->assertSame(['example.com', 'example.net'], array_keys($results));
        $this->assertTrue($results['example.com']->available);
        $this->assertFalse($registrar->supportsTransfer());
        $this->assertFalse($registrar->transfer(new Service)->success);
    }

    public function test_fake_registrar_returns_one_result_per_domain(): void
    {
        $registrar = new FakeDomainRegistrar;
        $results = $registrar->checkAvailabilityBatch(['example.com', 'taken.net']);

        $this->assertTrue($results['example.com']->available);
        $this->assertFalse($results['taken.net']->available);
        $this->assertTrue($registrar->supportsTransfer());
    }

    public function test_optional_registrar_operations_fail_explicitly(): void
    {
        $registrar = new MinimalDomainRegistrar;
        $service = new Service;

        $this->assertFalse($registrar->updateNameservers($service, ['ns1.example.net'])->success);
        $this->assertFalse($registrar->createDnsRecord($service, [])->success);
        $this->assertFalse($registrar->updateDnsRecord($service, '1', [])->success);
        $this->assertFalse($registrar->deleteDnsRecord($service, '1')->success);
        $this->expectException(\BadMethodCallException::class);
        $registrar->getDnsRecords($service);
    }

    public function test_default_dns_values_are_normalized_and_validated(): void
    {
        $validated = app(DomainDefaultsService::class)->validate([
            'default_nameservers' => ['NS1.EXAMPLE.NET.', 'ns2.example.net'],
            'default_nameserver_ips' => [
                ['ipv4' => '192.0.2.1', 'ipv6' => '2001:db8::1'],
                ['ipv4' => '192.0.2.2', 'ipv6' => ''],
            ],
            'default_dns_records' => [
                ['type' => 'A', 'name' => '@', 'value' => '192.0.2.10', 'ttl' => '3600'],
                ['type' => 'MX', 'name' => '@', 'value' => 'MAIL.EXAMPLE.NET.', 'ttl' => 3600, 'priority' => '10'],
            ],
        ]);

        $this->assertSame(['ns1.example.net', 'ns2.example.net'], $validated['default_nameservers']);
        $this->assertSame('192.0.2.1', $validated['default_nameserver_ips'][0]['ipv4']);
        $this->assertSame('2001:db8::1', $validated['default_nameserver_ips'][0]['ipv6']);
        $this->assertNull($validated['default_nameserver_ips'][1]['ipv6']);
        $this->assertSame(3600, $validated['default_dns_records'][0]['ttl']);
        $this->assertSame(10, $validated['default_dns_records'][1]['priority']);
    }

    public function test_cname_conflicts_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(DomainDefaultsService::class)->validate([
            'default_nameservers' => [],
            'default_dns_records' => [
                ['type' => 'CNAME', 'name' => 'www', 'value' => 'target.example.net', 'ttl' => 3600],
                ['type' => 'A', 'name' => 'www', 'value' => '192.0.2.10', 'ttl' => 3600],
            ],
        ]);
    }

    public function test_active_tld_requires_two_nameservers(): void
    {
        $this->expectException(ValidationException::class);
        app(DomainDefaultsService::class)->validate([
            'status' => 'active',
            'default_nameservers' => ['ns1.example.net'],
            'default_dns_records' => [],
        ]);
    }

    public function test_catalog_markup_uses_explicit_exchange_rate_and_rounds(): void
    {
        $this->assertSame(14.45, app(DomainCatalogService::class)->sellingPrice(10, 10, 1.25, 1.2));
    }
}

class MinimalDomainRegistrar extends AbstractDomainRegistrar
{
    public function uuid(): string
    {
        return 'minimal';
    }

    public function title(): string
    {
        return 'Minimal';
    }

    public function testConnection(array $params): ConnectionResponse
    {
        return new ConnectionResponse(new Response(200));
    }

    public function checkAvailability(string $domain): DomainAvailabilityDTO
    {
        return new DomainAvailabilityDTO($domain, true);
    }

    public function register(Service $service): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, true, 'Registered');
    }

    public function renew(Service $service, int $years): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, true, 'Renewed');
    }
}
