<?php

namespace App\Modules\OpenProvider;

use App\Abstracts\AbstractDomainRegistrar;
use App\Contracts\Domain\DomainDnsInitializationInterface;
use App\DTO\Domain\DomainAvailabilityDTO;
use App\DTO\Domain\DomainInfoDTO;
use App\DTO\Provisioning\ConnectionResponse;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Models\Store\DomainTld;
use App\Services\Store\RecurringService;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use libphonenumber\PhoneNumberUtil;
use RuntimeException;
use Throwable;

class OpenProviderDomainRegistrar extends AbstractDomainRegistrar implements \App\Contracts\Domain\DomainCatalogInterface, DomainDnsInitializationInterface
{
    public const PRODUCTION_URL = 'https://api.openprovider.eu';

    public const SANDBOX_URL = 'https://api.sandbox.openprovider.nl';

    /** @var callable(string, string, string, ?string): object|null */
    private $clientFactory;

    /** @var callable(): ?Server|null */
    private $defaultServerResolver;

    public function __construct(?callable $clientFactory = null, ?callable $defaultServerResolver = null)
    {
        $this->clientFactory = $clientFactory;
        $this->defaultServerResolver = $defaultServerResolver;
    }

    public function dnsCapabilities(Server $server): array
    {
        return ['nameservers' => ['ns1.openprovider.nl', 'ns2.openprovider.be', 'ns3.openprovider.eu'], 'types' => ['A', 'AAAA', 'CNAME', 'MX', 'TXT']];
    }

    public function initializationRecords(Service $service): array
    {
        $client = $this->clientForService($service);
        $name = $this->domain($service);
        $this->ensureZone($client, $name);
        $records = [];
        $offset = 0;
        do {
            $page = $client->listZoneRecords($name, $offset);
            if (! isset($page['results']) || ! is_array($page['results'])) {
                throw new RuntimeException('Openprovider returned an incomplete DNS zone');
            }
            $records = array_merge($records, $page['results']);
            $count = count($page['results']);
            $offset += $count;
        } while ($count > 0 && (isset($page['total']) ? $offset < (int) $page['total'] : $count === 500));

        return array_map(fn ($record) => [
            'name' => $record['name'] ?? '@', 'type' => strtoupper($record['type'] ?? ''),
            'value' => $record['value'] ?? $record['ip'] ?? '', 'ttl' => $record['ttl'] ?? 3600,
            'priority' => $record['prio'] ?? 0,
        ], $records);
    }

    public function catalogPage(Server $server, int $offset = 0, int $limit = 100): \App\DTO\Domain\DomainCatalogPage
    {
        $response = $this->client($server->toArray(), $server->hasMetadata('test_mode'))->listTlds($offset, $limit);
        if (! isset($response['results']) || ! is_array($response['results'])) {
            throw new RuntimeException('Openprovider returned an incomplete TLD catalog');
        }
        $prices = [];
        foreach ($response['results'] as $tld) {
            // The TLD endpoint exposes standard one-year costs, not multi-year quotes.
            // Do not invent multi-year costs or import domain-specific premium prices.
            if (($tld['min_period'] ?? 1) > 1 || ($tld['max_period'] ?? 1) < 1) {
                continue;
            }
            foreach (['register' => 'create_price', 'renew' => 'renew_price', 'transfer' => 'transfer_price'] as $action => $key) {
                if (($action === 'renew' && empty($tld['renew_available'])) || ($action === 'transfer' && empty($tld['transfer_available']))) {
                    continue;
                }
                $price = $tld['prices'][$key]['reseller'] ?? null;
                if (! is_array($price) || ! isset($price['price'], $price['currency']) || ! is_numeric($price['price']) || $price['price'] < 0) {
                    continue;
                }
                $prices[] = [
                    'extension' => '.'.ltrim(strtolower($tld['name']), '.'),
                    'action' => $action, 'billing' => 'annually', 'cost' => (float) $price['price'],
                    'currency' => strtoupper($price['currency']),
                ];
            }
        }
        $count = count($response['results']);
        $next = $offset + $count;
        $hasMore = $count > 0 && (isset($response['total']) ? $next < (int) $response['total'] : $count === $limit);

        return new \App\DTO\Domain\DomainCatalogPage($prices, $hasMore ? $next : null, isset($response['total']) ? (int) $response['total'] : null);
    }

    public function uuid(): string
    {
        return 'openprovider';
    }

    public function title(): string
    {
        return 'Openprovider';
    }

    public function validate(): array
    {
        return [
            'hostname' => 'required|string|in:openprovider',
            'address' => 'nullable|url:http,https',
            'username' => 'required|string',
            'password' => 'required|string',
        ];
    }

    public function testConnection(array $params): ConnectionResponse
    {
        try {
            $account = $this->client($params, ! empty($params['test_mode']))->login();

            return new ConnectionResponse(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'message' => 'Connected to Openprovider successfully',
                'reseller_id' => $account['reseller_id'] ?? null,
            ], JSON_THROW_ON_ERROR)));
        } catch (Throwable $e) {
            $status = in_array((int) $e->getCode(), [401, 403], true) ? 401 : 500;

            return new ConnectionResponse(new Response($status, [], $e->getMessage()));
        }
    }

    public function checkAvailability(string $domain): DomainAvailabilityDTO
    {
        $domain = $this->normalizeDomain($domain);
        try {
            $response = $this->defaultClient()->checkDomain($this->domainParts($domain));
            $result = $response['results'][0] ?? [];
            $status = strtolower((string) ($result['status'] ?? ''));
            $available = in_array($status, ['free', 'available'], true);

            return new DomainAvailabilityDTO((string) ($result['domain'] ?? $domain), $available, $available ? null : (($result['reason'] ?? null) ?: 'Domain is not available'));
        } catch (Throwable $e) {
            return new DomainAvailabilityDTO($domain, false, $e->getMessage());
        }
    }

    public function register(Service $service): ServiceStateChangeDTO
    {
        try {
            $client = $this->clientForService($service);
            $data = $service->data ?? [];
            $handle = trim((string) ($data['openprovider_contact_handle'] ?? ''));
            if ($handle === '') {
                $contact = $client->createContact($this->contact($service));
                $handle = (string) ($contact['handle'] ?? '');
                if ($handle === '') {
                    throw new RuntimeException('Openprovider did not return a contact handle');
                }
                $data['openprovider_contact_id'] = isset($contact['id']) ? (string) $contact['id'] : null;
                $data['openprovider_contact_handle'] = $handle;
                $service->data = $data;
                $service->save();
            }

            $nameservers = $this->serviceNameservers($service);
            $response = $client->createDomain([
                'domain' => $this->domainParts($this->domain($service)),
                'owner_handle' => $handle,
                'admin_handle' => $handle,
                'tech_handle' => $handle,
                'billing_handle' => $handle,
                'period' => $this->years($service),
                'unit' => 'y',
                'autorenew' => 'off',
                'is_private_whois_enabled' => $this->whoisPrivacy($service),
                'name_servers' => $this->apiNameservers($nameservers),
            ]);
            $this->syncService($service, $response, $nameservers);

            return $this->state($service, true, 'Domain registration submitted', $response);
        } catch (Throwable $e) {
            return $this->state($service, false, $e->getMessage());
        }
    }

    public function renew(Service $service, int $years): ServiceStateChangeDTO
    {
        try {
            $client = $this->clientForService($service);
            $id = $this->domainId($service, $client);
            $response = $client->renewDomain($id, ['id' => (int) $id, 'domain' => $this->domainParts($this->domain($service)), 'period' => max(1, min(10, $years))]);
            $remote = $client->getDomain($id);
            $this->syncService($service, $remote);

            return $this->state($service, true, 'Domain renewal submitted', $response);
        } catch (Throwable $e) {
            return $this->state($service, false, $e->getMessage());
        }
    }

    public function getDomain(Service $service): DomainInfoDTO
    {
        $client = $this->clientForService($service);
        $id = $this->domainId($service, $client);
        $data = $client->getDomain($id);
        $this->syncService($service, $data);

        return new DomainInfoDTO(
            $this->remoteDomain($data, $this->domain($service)),
            (string) ($data['status'] ?? 'UNKNOWN'),
            $this->carbonDate($data['creation_date'] ?? $data['order_date'] ?? null),
            $this->carbonDate($data['renewal_date'] ?? $data['expiration_date'] ?? null),
            $this->extractNameservers($data),
            (string) ($data['id'] ?? $id),
        );
    }

    public function getNameservers(Service $service): array
    {
        try {
            return $this->getDomain($service)->nameservers ?: $this->serviceNameservers($service);
        } catch (Throwable) {
            return $this->serviceNameservers($service);
        }
    }

    public function updateNameservers(Service $service, array $nameservers): ServiceStateChangeDTO
    {
        try {
            $nameservers = $this->normalizeNameservers($nameservers);
            $this->assertNameservers($nameservers);
            $client = $this->clientForService($service);
            $id = $this->domainId($service, $client);
            $response = $client->updateDomain($id, ['id' => (int) $id, 'domain' => $this->domainParts($this->domain($service)), 'name_servers' => $this->apiNameservers($nameservers)]);
            $data = $service->data ?? [];
            $data['nameservers'] = $nameservers;
            $service->data = $data;
            $service->save();

            return $this->state($service, true, 'Nameservers updated', $response);
        } catch (Throwable $e) {
            return $this->state($service, false, $e->getMessage());
        }
    }

    public function getDnsRecords(Service $service): array
    {
        if (! $this->dnsManagement($service)) {
            return [];
        }
        try {
            $results = $this->clientForService($service)->listZoneRecords($this->domain($service))['results'] ?? [];

            return collect($results)->filter(fn ($item) => is_array($item))->map(function (array $item) {
                $record = ['name' => (string) ($item['name'] ?? '@'), 'type' => strtoupper((string) ($item['type'] ?? '')), 'value' => (string) ($item['value'] ?? $item['ip'] ?? ''), 'ttl' => (int) ($item['ttl'] ?? 3600)];
                if (isset($item['prio'])) {
                    $record['priority'] = (int) $item['prio'];
                }
                $record['id'] = $this->recordId($record);

                return $record;
            })->values()->all();
        } catch (Throwable) {
            return [];
        }
    }

    public function createDnsRecord(Service $service, array $record): ServiceStateChangeDTO
    {
        return $this->mutateDns($service, 'add', $record, null, 'DNS record created');
    }

    public function updateDnsRecord(Service $service, string $recordId, array $record): ServiceStateChangeDTO
    {
        return $this->mutateDns($service, 'update', $record, $recordId, 'DNS record updated');
    }

    public function deleteDnsRecord(Service $service, string $recordId): ServiceStateChangeDTO
    {
        return $this->mutateDns($service, 'remove', [], $recordId, 'DNS record deleted');
    }

    private function mutateDns(Service $service, string $action, array $record, ?string $recordId, string $message): ServiceStateChangeDTO
    {
        if (! $this->dnsManagement($service)) {
            return $this->state($service, false, 'DNS management is disabled for this domain');
        }
        try {
            $client = $this->clientForService($service);
            $domain = $this->domain($service);
            $this->ensureZone($client, $domain);
            $payload = [];
            if ($action === 'add') {
                $payload['records']['add'][] = $this->apiRecord($this->normalizeRecord($record));
            }
            if ($action === 'remove') {
                $payload['records']['remove'][] = $this->apiRecord($this->decodeRecordId((string) $recordId));
            }
            if ($action === 'update') {
                $payload['records']['update'][] = ['original_record' => $this->apiRecord($this->decodeRecordId((string) $recordId)), 'record' => $this->apiRecord($this->normalizeRecord($record))];
            }
            $response = $client->updateZone($domain, $payload);

            return $this->state($service, true, $message, $response);
        } catch (Throwable $e) {
            return $this->state($service, false, $e->getMessage());
        }
    }

    private function client(array $params, bool $sandbox = false): object
    {
        $username = trim((string) ($params['username'] ?? ''));
        $password = trim((string) ($params['password'] ?? ''));
        if ($username === '' || $password === '') {
            throw new RuntimeException('Openprovider server credentials are missing');
        }
        $url = trim((string) ($params['address'] ?? ''));
        if ($url !== '') {
            if (filter_var($url, FILTER_VALIDATE_URL) === false || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                throw new RuntimeException('Openprovider endpoint must be a valid HTTP(S) URL');
            }
            $url = rtrim($url, '/');
        } else {
            $url = $this->baseUrl($sandbox);
        }
        $ip = trim((string) ($params['ip'] ?? '')) ?: null;

        return $this->clientFactory ? ($this->clientFactory)($username, $password, $url, $ip) : new OpenProviderApiClient($username, $password, $url, $ip);
    }

    private function clientForService(Service $service): object
    {
        if ($service->server === null) {
            throw new RuntimeException('Openprovider server is missing');
        }

        return $this->client($service->server->toArray(), $service->server->hasMetadata('test_mode'));
    }

    private function defaultClient(): object
    {
        $server = $this->defaultServerResolver ? ($this->defaultServerResolver)() : Server::query()->where('type', 'domain')->where('status', 'active')->where('hostname', $this->uuid())->first();
        if ($server === null) {
            throw new RuntimeException('Openprovider server is missing');
        }

        return $this->client($server->toArray(), $server->hasMetadata('test_mode'));
    }

    private function baseUrl(bool $sandbox = false): string
    {
        if ($sandbox) {
            return self::SANDBOX_URL;
        }

        return self::PRODUCTION_URL;
    }

    private function domainId(Service $service, object $client): string
    {
        $id = trim((string) (($service->data ?? [])['registrar_id'] ?? ''));
        if ($id !== '') {
            return $id;
        }
        $result = $client->listDomains(['full_name' => $this->domain($service), 'limit' => 1]);
        $remote = $result['results'][0] ?? null;
        if (! is_array($remote) || empty($remote['id'])) {
            throw new RuntimeException('Openprovider domain was not found');
        }
        $this->syncService($service, $remote);

        return (string) $remote['id'];
    }

    private function contact(Service $service): array
    {
        $customer = $service->customer;
        [$countryCode, $areaCode, $subscriber] = $this->phone($customer->phone, $customer->country);
        [$street, $number] = $this->street((string) $customer->address);

        return [
            'name' => ['first_name' => (string) $customer->firstname, 'last_name' => (string) $customer->lastname, 'full_name' => trim($customer->firstname.' '.$customer->lastname)],
            'company_name' => (string) ($customer->company_name ?? ''),
            'email' => (string) $customer->email,
            'phone' => ['country_code' => $countryCode, 'area_code' => $areaCode, 'subscriber_number' => $subscriber],
            'address' => ['street' => $street, 'number' => $number, 'suffix' => (string) ($customer->address2 ?? ''), 'zipcode' => (string) $customer->zipcode, 'city' => (string) $customer->city, 'state' => (string) ($customer->region ?: $customer->city), 'country' => strtoupper((string) $customer->country)],
            'locale' => $customer->locale ?: 'en_US',
            'vat' => (string) ($customer->vat_number ?? ''),
        ];
    }

    private function phone(?string $phone, ?string $country): array
    {
        $util = PhoneNumberUtil::getInstance();
        try {
            $number = $util->parse((string) $phone, $country ? strtoupper($country) : null);
        } catch (\libphonenumber\NumberParseException $e) {
            throw new RuntimeException('Openprovider requires a valid customer phone number', 0, $e);
        }
        if (! $util->isValidNumber($number)) {
            throw new RuntimeException('Openprovider requires a valid customer phone number');
        }
        $national = $util->getNationalSignificantNumber($number);
        $areaLength = $util->getLengthOfNationalDestinationCode($number);
        if ($areaLength === 0 || $areaLength >= strlen($national)) {
            throw new RuntimeException('Openprovider could not determine the customer telephone region code');
        }

        return ['+'.(string) $number->getCountryCode(), substr($national, 0, $areaLength), substr($national, $areaLength)];
    }

    private function street(string $address): array
    {
        if (preg_match('/^(.*?)[,\s]+([0-9]+[a-zA-Z-]*)$/', trim($address), $matches)) {
            return [trim($matches[1]), $matches[2]];
        }

        return [trim($address), ''];
    }

    private function syncService(Service $service, array $remote, array $nameservers = []): void
    {
        $data = $service->data ?? [];
        if (isset($remote['id'])) {
            $data['registrar_id'] = (string) $remote['id'];
        }
        $data['registrar_status'] = strtolower((string) ($remote['status'] ?? $data['registrar_status'] ?? 'pending'));
        $data['created_at'] = $this->dateString($remote['creation_date'] ?? $remote['order_date'] ?? $remote['activation_date'] ?? null) ?? ($data['created_at'] ?? now()->toDateString());
        $data['expires_at'] = $this->dateString($remote['renewal_date'] ?? $remote['expiration_date'] ?? null) ?? ($data['expires_at'] ?? optional($service->expires_at)->toDateString());
        $remoteNameservers = $this->extractNameservers($remote);
        if ($nameservers !== [] || $remoteNameservers !== []) {
            $data['nameservers'] = $nameservers ?: $remoteNameservers;
        }
        $service->data = $data;
        $service->save();
    }

    private function state(Service $service, bool $success, string $message, array $data = []): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, $success, $message, $data);
    }

    private function years(Service $service): int
    {
        return max(1, min(10, (int) ceil((app(RecurringService::class)->get($service->billing)['months'] ?? 12) / 12)));
    }

    private function domain(Service $service): string
    {
        return $this->normalizeDomain((string) (($service->data ?? [])['domain'] ?? $service->name));
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain, " .\t\n\r\0\x0B"));
        $this->domainParts($domain);

        return $domain;
    }

    private function domainParts(string $domain): array
    {
        $position = strrpos($domain, '.');
        if ($position === false || $position === 0 || $position === strlen($domain) - 1) {
            throw new RuntimeException('Invalid domain name');
        }

        return ['name' => substr($domain, 0, $position), 'extension' => substr($domain, $position + 1)];
    }

    private function remoteDomain(array $data, string $fallback): string
    {
        $domain = $data['domain'] ?? null;

        return is_array($domain) ? (($domain['name'] ?? '').'.'.($domain['extension'] ?? '')) : (is_string($domain) ? $domain : $fallback);
    }

    private function serviceNameservers(Service $service): array
    {
        $nameservers = $this->normalizeNameservers(($service->data ?? [])['nameservers'] ?? []);
        $this->assertNameservers($nameservers);

        return $nameservers;
    }

    private function normalizeNameservers(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(fn ($item) => strtolower(rtrim(trim((string) (is_array($item) ? ($item['name'] ?? '') : $item)), '.')), $items))));
    }

    private function assertNameservers(array $items): void
    {
        if (count($items) < 2 || count($items) > 8) {
            throw new RuntimeException('Openprovider requires between two and eight nameservers');
        }
    }

    private function apiNameservers(array $items): array
    {
        return array_map(fn ($name, $index) => ['name' => $name, 'seq_nr' => $index + 1], $items, array_keys($items));
    }

    private function extractNameservers(array $data): array
    {
        return $this->normalizeNameservers($data['name_servers'] ?? []);
    }

    private function whoisPrivacy(Service $service): bool
    {
        return (bool) (($service->data ?? [])['whois_privacy'] ?? $this->tld($service)?->whois_privacy ?? false);
    }

    private function dnsManagement(Service $service): bool
    {
        return (bool) (($service->data ?? [])['dns_management'] ?? $this->tld($service)?->dns_management ?? false);
    }

    private function tld(Service $service): ?DomainTld
    {
        $extension = $this->domainParts($this->domain($service))['extension'];

        return DomainTld::where('extension', '.'.$extension)->first();
    }

    private function ensureZone(object $client, string $domain): void
    {
        try {
            $client->getZone($domain);
        } catch (Throwable $e) {
            if ((int) $e->getCode() !== 404) {
                throw $e;
            }
            $client->createZone(['domain' => $this->domainParts($domain), 'type' => 'master']);
        }
    }

    private function normalizeRecord(array $record): array
    {
        $normalized = ['name' => trim((string) ($record['name'] ?? '@')) ?: '@', 'type' => strtoupper(trim((string) ($record['type'] ?? ''))), 'value' => trim((string) ($record['value'] ?? '')), 'ttl' => max(0, (int) ($record['ttl'] ?? 3600))];
        if (! in_array($normalized['type'], ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV'], true) || $normalized['value'] === '') {
            throw new RuntimeException('Unsupported or incomplete Openprovider DNS record');
        }
        if (isset($record['priority']) && $record['priority'] !== '') {
            $normalized['priority'] = (int) $record['priority'];
        }

        return $normalized;
    }

    private function apiRecord(array $record): array
    {
        return array_filter(['name' => $record['name'] === '@' ? $this->normalizeRecordName('@') : $record['name'], 'type' => $record['type'], 'value' => $record['value'], 'ttl' => (int) ($record['ttl'] ?? 3600), 'prio' => isset($record['priority']) ? (int) $record['priority'] : null], fn ($value) => $value !== null);
    }

    private function normalizeRecordName(string $name): string
    {
        return $name === '@' ? '' : $name;
    }

    private function recordId(array $record): string
    {
        return rtrim(strtr(base64_encode(json_encode($record, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    private function decodeRecordId(string $id): array
    {
        $padding = strlen($id) % 4;
        $decoded = base64_decode(strtr($id.($padding ? str_repeat('=', 4 - $padding) : ''), '-_', '+/'), true);
        $record = $decoded === false ? null : json_decode($decoded, true);
        if (! is_array($record) || ! isset($record['name'], $record['type'], $record['value'])) {
            throw new RuntimeException('Invalid DNS record identifier');
        }

        return $record;
    }

    private function carbonDate(mixed $date): ?Carbon
    {
        if (! is_string($date) || trim($date) === '') {
            return null;
        } try {
            return Carbon::parse($date);
        } catch (Throwable) {
            return null;
        }
    }

    private function dateString(mixed $date): ?string
    {
        return $this->carbonDate($date)?->toDateString();
    }
}
