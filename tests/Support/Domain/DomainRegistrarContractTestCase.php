<?php

namespace Tests\Support\Domain;

use App\Contracts\Domain\DomainRegistrarInterface;
use App\Contracts\Domain\DomainTransferStatusInterface;
use App\Models\Account\Customer;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use Tests\TestCase;

/**
 * Behaviour every domain registrar module must share.
 * A module extends this class and only provides its fake API wiring.
 */
abstract class DomainRegistrarContractTestCase extends TestCase
{
    /** Registrar wired to a fake API that answers every call successfully. */
    abstract protected function makeRegistrar(): DomainRegistrarInterface;

    /** Registrar wired to a fake API that fails at transport level on every call. */
    abstract protected function makeFailingRegistrar(): DomainRegistrarInterface;

    /** Calls received by the fake API since the registrar was made. */
    abstract protected function apiCallCount(): int;

    public function test_identity_matches_the_server_validation_rules(): void
    {
        $registrar = $this->makeRegistrar();
        $rules = $registrar->validate();

        $this->assertNotSame('', $registrar->uuid());
        $this->assertNotSame('', $registrar->title());
        $this->assertStringContainsString('in:'.$registrar->uuid(), $rules['hostname'] ?? '');
        $this->assertStringContainsString('required', $rules['username'] ?? '');
        $this->assertStringContainsString('required', $rules['password'] ?? '');
    }

    public function test_connection_without_credentials_fails_without_any_api_call(): void
    {
        $registrar = $this->makeRegistrar();

        $response = $registrar->testConnection(['hostname' => $registrar->uuid(), 'username' => '', 'password' => '']);

        $this->assertFalse($response->successful());
        $this->assertSame(0, $this->apiCallCount());
    }

    public function test_renewal_reads_credentials_hidden_from_server_serialization(): void
    {
        $registrar = $this->makeRegistrar();

        $result = $registrar->renew($this->service($registrar), 1);

        $this->assertTrue($result->success, $result->message);
        $this->assertGreaterThan(0, $this->apiCallCount());
    }

    public function test_registration_requires_two_nameservers_before_any_api_call(): void
    {
        $registrar = $this->makeRegistrar();

        $result = $registrar->register($this->service($registrar, ['nameservers' => ['ns1.example.com']]));

        $this->assertFalse($result->success);
        $this->assertSame(0, $this->apiCallCount());
    }

    public function test_nameserver_update_requires_two_nameservers_before_any_api_call(): void
    {
        $registrar = $this->makeRegistrar();
        $service = $this->service($registrar);

        $result = $registrar->updateNameservers($service, ['ns1.example.com']);

        $this->assertFalse($result->success);
        $this->assertSame(['ns1.example.com', 'ns2.example.com'], $service->data['nameservers']);
        $this->assertSame(0, $this->apiCallCount());
    }

    public function test_invalid_dns_record_ids_are_refused_before_any_api_call(): void
    {
        $registrar = $this->makeRegistrar();
        $service = $this->service($registrar);
        $record = ['name' => 'www', 'type' => 'A', 'value' => '192.0.2.1', 'ttl' => 3600];

        $this->assertFalse($registrar->updateDnsRecord($service, 'not-a-record', $record)->success);
        $this->assertFalse($registrar->deleteDnsRecord($service, 'not-a-record')->success);
        $this->assertSame(0, $this->apiCallCount());
    }

    public function test_dns_is_refused_when_dns_management_is_disabled(): void
    {
        $registrar = $this->makeRegistrar();
        $service = $this->service($registrar, ['dns_management' => false]);
        $record = ['name' => 'www', 'type' => 'A', 'value' => '192.0.2.1', 'ttl' => 3600];

        $this->assertSame([], $registrar->getDnsRecords($service));
        $this->assertFalse($registrar->createDnsRecord($service, $record)->success);
        $this->assertFalse($registrar->deleteDnsRecord($service, 'not-a-record')->success);
        $this->assertSame(0, $this->apiCallCount());
    }

    public function test_transfer_requires_an_auth_code_before_any_api_call(): void
    {
        $registrar = $this->makeRegistrar();

        $result = $registrar->transfer($this->service($registrar, ['operation' => 'transfer']));

        $this->assertTrue($registrar->supportsTransfer());
        // Without a status report, DomainTransferMonitor would leave transferred services pending forever.
        $this->assertInstanceOf(DomainTransferStatusInterface::class, $registrar);
        $this->assertFalse($result->success);
        $this->assertSame('Authorization code is required', $result->message);
        $this->assertSame(0, $this->apiCallCount());
    }

    /** ServerTypeTrait::deliver() keeps the service pending only for this exact state. */
    public function test_submitted_transfer_is_pending_and_forgets_the_auth_code(): void
    {
        $registrar = $this->makeRegistrar();
        $service = $this->service($registrar, ['operation' => 'transfer', 'auth_code' => 'EPP-123']);

        $result = $registrar->transfer($service);

        $this->assertTrue($result->success, $result->message);
        $this->assertSame('transfer_pending', $service->data['registrar_status']);
        $this->assertTrue($service->data['transfer_submitted']);
        $this->assertArrayNotHasKey('auth_code', $service->data);
        $this->assertGreaterThan(0, $this->apiCallCount());
    }

    public function test_availability_failures_are_reported_instead_of_thrown(): void
    {
        $result = $this->makeFailingRegistrar()->checkAvailability('example.com');

        $this->assertFalse($result->available);
    }

    /** Server credentials are set like a real record: they stay hidden from toArray(). */
    protected function server(DomainRegistrarInterface $registrar): Server
    {
        $server = new Server(['hostname' => $registrar->uuid(), 'type' => 'domain', 'username' => 'user', 'password' => 'secret']);
        $server->setRelation('metadata', collect());

        return $server;
    }

    protected function service(DomainRegistrarInterface $registrar, array $data = []): Service
    {
        $customer = new Customer([
            'firstname' => 'Jane', 'lastname' => 'Doe', 'email' => 'jane@example.com', 'phone' => '+33610101010',
            'address' => '1 rue de Paris', 'zipcode' => '75001', 'city' => 'Paris', 'region' => 'Paris', 'country' => 'FR',
        ]);
        $service = new class extends Service
        {
            public function save(array $options = []): bool
            {
                return true;
            }
        };
        $service->name = 'example.com';
        $service->billing = 'annually';
        $service->data = array_merge([
            'domain' => 'example.com',
            'nameservers' => ['ns1.example.com', 'ns2.example.com'],
            'dns_management' => true,
            'whois_privacy' => false,
        ], $data);
        $service->setRelation('server', $this->server($registrar));
        $service->setRelation('customer', $customer);

        return $service;
    }
}
