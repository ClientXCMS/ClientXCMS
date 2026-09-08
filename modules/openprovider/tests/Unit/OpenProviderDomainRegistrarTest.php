<?php

namespace App\Modules\OpenProvider\Tests\Unit;

require_once dirname(__DIR__, 2).'/src/OpenProviderApiClient.php';
require_once dirname(__DIR__, 2).'/src/OpenProviderDomainRegistrar.php';

use App\Models\Account\Customer;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Modules\OpenProvider\OpenProviderDomainRegistrar;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenProviderDomainRegistrarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_connection_uses_official_endpoint_by_default(): void
    {
        $url = null;
        $registrar = new OpenProviderDomainRegistrar(function ($username, $password, $baseUrl) use (&$url) {
            $url = $baseUrl;

            return new OpenProviderFakeClient;
        });
        $response = $registrar->testConnection(['username' => 'user', 'password' => 'secret']);
        $this->assertTrue($response->successful());
        $this->assertSame(OpenProviderDomainRegistrar::PRODUCTION_URL, $url);
    }

    public function test_connection_accepts_a_custom_endpoint(): void
    {
        $url = null;
        $registrar = new OpenProviderDomainRegistrar(function ($username, $password, $baseUrl) use (&$url) {
            $url = $baseUrl;

            return new OpenProviderFakeClient;
        });
        $registrar->testConnection(['username' => 'user', 'password' => 'secret', 'address' => 'https://openprovider.test/api/']);
        $this->assertSame('https://openprovider.test/api', $url);
    }

    public function test_invalid_custom_endpoint_is_rejected(): void
    {
        $response = (new OpenProviderDomainRegistrar)->testConnection(['username' => 'user', 'password' => 'secret', 'address' => 'invalid']);
        $this->assertFalse($response->successful());
        $this->assertStringContainsString('valid HTTP(S) URL', $response->toString());
    }

    public function test_catalog_maps_only_documented_one_year_prices(): void
    {
        $server = new Server(['hostname' => 'openprovider', 'username' => 'user', 'password' => 'secret']);
        $server->setRelation('metadata', collect());
        $fake = new OpenProviderFakeClient;
        $fake->tlds = [[
            'name' => 'com',
            'min_period' => 1,
            'max_period' => 10,
            'renew_available' => true,
            'transfer_available' => true,
            'prices' => [
                'create_price' => ['reseller' => ['price' => 8.5, 'currency' => 'eur']],
                'renew_price' => ['reseller' => ['price' => 9.5, 'currency' => 'EUR']],
            ],
        ]];

        $page = (new OpenProviderDomainRegistrar(fn () => $fake))->catalogPage($server, 0, 100);

        $this->assertSame(['register', 'renew'], array_column($page->prices, 'action'));
        $this->assertSame(['annually', 'annually'], array_column($page->prices, 'billing'));
        $this->assertNull($page->nextOffset);
    }

    public function test_availability_maps_free_and_active_statuses(): void
    {
        $server = new Server(['hostname' => 'openprovider', 'username' => 'user', 'password' => 'secret']);
        $server->setRelation('metadata', collect());
        $fake = new OpenProviderFakeClient;
        $registrar = new OpenProviderDomainRegistrar(fn () => $fake, fn () => $server);
        $this->assertTrue($registrar->checkAvailability('Example.COM')->available);
        $fake->availability = ['domain' => 'example.com', 'status' => 'active', 'reason' => 'Registered'];
        $result = $registrar->checkAvailability('example.com');
        $this->assertFalse($result->available);
        $this->assertSame('Registered', $result->message);
    }

    public function test_domain_info_prefers_renewal_date_and_normalizes_nameservers(): void
    {
        $fake = new OpenProviderFakeClient;
        $fake->domain = ['id' => 88, 'domain' => ['name' => 'example', 'extension' => 'com'], 'status' => 'active', 'creation_date' => '2026-01-01', 'renewal_date' => '2027-01-10', 'expiration_date' => '2027-02-10', 'name_servers' => [['name' => 'NS1.EXAMPLE.COM.'], ['name' => 'ns2.example.com']]];
        $service = $this->service($fake, ['domain' => 'example.com', 'registrar_id' => '88', 'dns_management' => true]);
        $info = $this->registrar($fake)->getDomain($service);
        $this->assertSame('2027-01-10', $info->expiresAt?->toDateString());
        $this->assertSame(['ns1.example.com', 'ns2.example.com'], $info->nameservers);
        $this->assertSame('88', $service->data['registrar_id']);
    }

    public function test_missing_domain_id_is_resolved_by_full_name(): void
    {
        $fake = new OpenProviderFakeClient;
        $service = $this->service($fake, ['domain' => 'example.com', 'dns_management' => true]);
        $this->registrar($fake)->getDomain($service);
        $this->assertSame(['full_name' => 'example.com', 'limit' => 1], $fake->domainQuery);
        $this->assertSame('88', $service->data['registrar_id']);
    }

    public function test_registration_reuses_the_persisted_contact_handle(): void
    {
        $fake = new OpenProviderFakeClient;
        $service = $this->service($fake, [
            'domain' => 'example.com',
            'nameservers' => ['ns1.example.com', 'ns2.example.com'],
            'dns_management' => true,
            'whois_privacy' => false,
        ]);

        $registrar = $this->registrar($fake);
        $first = $registrar->register($service);
        $this->assertTrue($first->success, $first->message);
        $this->assertTrue($registrar->register($service)->success);

        $this->assertSame(1, $fake->contactCalls);
        $this->assertSame('JD123456-IE', $service->data['openprovider_contact_handle']);
        $this->assertSame('88', $service->data['registrar_id']);
        $this->assertSame('JD123456-IE', $fake->createdDomain['owner_handle']);
    }

    public function test_nameserver_update_only_persists_after_success(): void
    {
        $fake = new OpenProviderFakeClient;
        $service = $this->service($fake, ['domain' => 'example.com', 'registrar_id' => '88', 'nameservers' => ['old1.test', 'old2.test'], 'dns_management' => true]);
        $result = $this->registrar($fake)->updateNameservers($service, ['NS1.TEST.', 'ns2.test']);
        $this->assertTrue($result->success);
        $this->assertSame(['ns1.test', 'ns2.test'], $service->data['nameservers']);

        $fake->updateDomainError = true;
        $result = $this->registrar($fake)->updateNameservers($service, ['ns3.test', 'ns4.test']);
        $this->assertFalse($result->success);
        $this->assertSame(['ns1.test', 'ns2.test'], $service->data['nameservers']);
    }

    public function test_dns_mutations_create_a_missing_zone_and_reject_invalid_ids(): void
    {
        $fake = new OpenProviderFakeClient;
        $fake->zoneMissing = true;
        $service = $this->service($fake, ['domain' => 'example.com', 'registrar_id' => '88', 'dns_management' => true]);
        $result = $this->registrar($fake)->createDnsRecord($service, ['name' => 'www', 'type' => 'A', 'value' => '192.0.2.1', 'ttl' => 900]);
        $this->assertTrue($result->success);
        $this->assertTrue($fake->zoneCreated);
        $this->assertSame('192.0.2.1', $fake->zoneUpdate['records']['add'][0]['value']);
        $this->assertFalse($this->registrar($fake)->deleteDnsRecord($service, 'invalid')->success);
    }

    public function test_dns_is_refused_when_disabled(): void
    {
        $fake = new OpenProviderFakeClient;
        $service = $this->service($fake, ['domain' => 'example.com', 'dns_management' => false]);
        $result = $this->registrar($fake)->createDnsRecord($service, ['name' => '@', 'type' => 'A', 'value' => '192.0.2.1']);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('disabled', $result->message);
    }

    public function test_registration_uses_customer_handle_and_one_login(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/v1beta/auth/login' => Http::response(['code' => 0, 'data' => ['token' => 'auth-token']]),
            '*/v1beta/customers' => Http::response(['code' => 0, 'data' => ['id' => 7, 'handle' => 'JD123456-IE']]),
            '*/v1beta/domains' => Http::response(['code' => 0, 'data' => ['id' => 88, 'status' => 'active']]),
        ]);
        $service = $this->service(new OpenProviderFakeClient, [
            'domain' => 'example.com',
            'nameservers' => ['ns1.example.com', 'ns2.example.com'],
            'whois_privacy' => false,
        ]);

        $result = (new OpenProviderDomainRegistrar)->register($service);

        $this->assertTrue($result->success, $result->message);
        $this->assertSame('JD123456-IE', $service->data['openprovider_contact_handle']);
        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/customers')
            && $request->hasHeader('Authorization', 'Bearer auth-token')
            && $request['email'] === 'jane@example.com'
            && $request['phone'] === ['country_code' => '+353', 'area_code' => '87', 'subscriber_number' => '1234567']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/domains')
            && $request->hasHeader('Authorization', 'Bearer auth-token')
            && $request['owner_handle'] === 'JD123456-IE'
            && $request['admin_handle'] === 'JD123456-IE'
            && $request['tech_handle'] === 'JD123456-IE'
            && $request['billing_handle'] === 'JD123456-IE');
    }

    public function test_registration_returns_customer_error_without_creating_domain(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/v1beta/auth/login' => Http::response(['code' => 0, 'data' => ['token' => 'auth-token']]),
            '*/v1beta/customers' => Http::response(['code' => 901, 'desc' => 'Empty username field!']),
        ]);
        $service = $this->service(new OpenProviderFakeClient, ['domain' => 'example.com']);

        $result = (new OpenProviderDomainRegistrar)->register($service);

        $this->assertFalse($result->success);
        $this->assertSame('Empty username field!', $result->message);
        Http::assertSentCount(2);
    }

    public function test_phone_is_split_into_country_area_and_subscriber(): void
    {
        $phone = new \ReflectionMethod(OpenProviderDomainRegistrar::class, 'phone');
        $registrar = new OpenProviderDomainRegistrar;
        foreach (['+33610101010', '06 10 10 10 10'] as $input) {
            $this->assertSame(['+33', '6', '10101010'], $phone->invoke($registrar, $input, 'FR'));
        }
        $this->assertSame(['+33', '1', '23456789'], $phone->invoke($registrar, '+33123456789', 'FR'));
        $this->assertSame(['+353', '87', '1234567'], $phone->invoke($registrar, '+353871234567', 'IE'));
    }

    public function test_invalid_phone_is_rejected_locally(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('valid customer phone number');
        (new \ReflectionMethod(OpenProviderDomainRegistrar::class, 'phone'))->invoke(new OpenProviderDomainRegistrar, '123', 'FR');
    }

    private function registrar(OpenProviderFakeClient $fake): OpenProviderDomainRegistrar
    {
        return new OpenProviderDomainRegistrar(fn () => $fake);
    }

    private function service(OpenProviderFakeClient $fake, array $data): Service
    {
        $server = new Server(['hostname' => 'openprovider', 'username' => 'user', 'password' => 'secret']);
        $server->setRelation('metadata', collect());
        $customer = new Customer(['firstname' => 'Jane', 'lastname' => 'Doe', 'email' => 'jane@example.com', 'phone' => '+353871234567', 'address' => 'Main Street 12', 'zipcode' => 'D02', 'city' => 'Dublin', 'region' => 'Dublin', 'country' => 'IE']);
        $service = new class extends Service
        {
            public function save(array $options = []): bool
            {
                return true;
            }
        };
        $service->name = 'example.com';
        $service->billing = 'yearly';
        $service->data = $data;
        $service->setRelation('server', $server);
        $service->setRelation('customer', $customer);

        return $service;
    }
}

class OpenProviderFakeClient
{
    public array $tlds = [];

    public array $availability = ['domain' => 'example.com', 'status' => 'free'];

    public array $domain = ['id' => 88, 'domain' => ['name' => 'example', 'extension' => 'com'], 'status' => 'active'];

    public array $domainQuery = [];

    public bool $updateDomainError = false;

    public bool $zoneMissing = false;

    public bool $zoneCreated = false;

    public array $zoneUpdate = [];

    public int $contactCalls = 0;

    public array $createdDomain = [];

    public function login(): array
    {
        return ['reseller_id' => 42];
    }

    public function listTlds(int $offset, int $limit): array
    {
        return ['results' => array_slice($this->tlds, $offset, $limit), 'total' => count($this->tlds)];
    }

    public function checkDomain(array $domain): array
    {
        return ['results' => [$this->availability]];
    }

    public function createContact(array $contact): array
    {
        $this->contactCalls++;

        return ['id' => 7, 'handle' => 'JD123456-IE'];
    }

    public function createDomain(array $payload): array
    {
        $this->createdDomain = $payload;

        return ['id' => 88, 'status' => 'active', 'activation_date' => '2026-09-02', 'renewal_date' => '2027-09-02'];
    }

    public function listDomains(array $query): array
    {
        $this->domainQuery = $query;

        return ['results' => [$this->domain]];
    }

    public function getDomain($id): array
    {
        return $this->domain;
    }

    public function updateDomain($id, array $payload): array
    {
        if ($this->updateDomainError) {
            throw new RuntimeException('Update failed');
        }

        return ['status' => 'active'];
    }

    public function getZone(string $name): array
    {
        if ($this->zoneMissing) {
            $this->zoneMissing = false;
            throw new RuntimeException('Not found', 404);
        }

        return ['name' => $name];
    }

    public function createZone(array $payload): array
    {
        $this->zoneCreated = true;

        return [];
    }

    public function updateZone(string $name, array $payload): array
    {
        $this->zoneUpdate = $payload;

        return [];
    }
}
