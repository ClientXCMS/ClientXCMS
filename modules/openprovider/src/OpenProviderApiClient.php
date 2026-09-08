<?php

namespace App\Modules\OpenProvider;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenProviderApiClient
{
    private ?string $token = null;

    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly string $baseUrl = 'https://api.openprovider.eu',
        private readonly ?string $ip = null,
        private readonly int $timeout = 30,
    ) {
        if (trim($username) === '' || trim($password) === '') {
            throw new RuntimeException('Openprovider server credentials are missing');
        }
    }

    public function login(): array
    {
        $response = $this->request()->post($this->url('/v1beta/auth/login'), array_filter([
            'username' => $this->username,
            'password' => $this->password,
            'ip' => $this->ip,
        ], fn ($value) => $value !== null && $value !== ''));
        $payload = $this->decode($response);
        $this->assertSuccessful($response, $payload);
        $token = $payload['data']['token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Openprovider did not return an authentication token');
        }
        $this->token = $token;

        return $payload['data'];
    }

    public function listTlds(int $offset, int $limit): array
    {
        return $this->call('GET', '/v1beta/tlds', [], ['offset' => $offset, 'limit' => $limit, 'with_price' => true, 'status' => 'ACT', 'order_by' => 'name', 'order' => 'ASC']);
    }

    public function createContact(array $contact): array
    {
        return $this->call('POST', '/v1beta/customers', $contact);
    }

    public function deleteContact(string $handle): array
    {
        return $this->call('DELETE', '/v1beta/customers/'.rawurlencode($handle));
    }

    public function getContact(string $handle): array
    {
        return $this->call('GET', '/v1beta/customers/'.rawurlencode($handle));
    }

    public function checkDomain(array $domain): array
    {
        return $this->call('POST', '/v1beta/domains/check', ['domains' => [$domain]]);
    }

    public function createDomain(array $payload): array
    {
        return $this->call('POST', '/v1beta/domains', $payload);
    }

    public function listDomains(array $query): array
    {
        return $this->call('GET', '/v1beta/domains', [], $query);
    }

    public function getDomain(int|string $id): array
    {
        return $this->call('GET', '/v1beta/domains/'.rawurlencode((string) $id));
    }

    public function updateDomain(int|string $id, array $payload): array
    {
        return $this->call('PUT', '/v1beta/domains/'.rawurlencode((string) $id), $payload);
    }

    public function renewDomain(int|string $id, array $payload): array
    {
        return $this->call('POST', '/v1beta/domains/'.rawurlencode((string) $id).'/renew', $payload);
    }

    public function getZone(string $name, bool $withRecords = false): array
    {
        return $this->call('GET', '/v1beta/dns/zones/'.rawurlencode($name), [], ['with_records' => $withRecords]);
    }

    public function createZone(array $payload): array
    {
        return $this->call('POST', '/v1beta/dns/zones', $payload);
    }

    public function listZoneRecords(string $name, int $offset = 0): array
    {
        return $this->call('GET', '/v1beta/dns/zones/'.rawurlencode($name).'/records', [], ['limit' => 500, 'offset' => $offset]);
    }

    public function updateZone(string $name, array $payload): array
    {
        return $this->call('PUT', '/v1beta/dns/zones/'.rawurlencode($name), $payload);
    }

    private function call(string $method, string $path, array $body = [], array $query = [], bool $retry = true): array
    {
        if ($this->token === null) {
            $this->login();
        }
        $options = [];
        if ($body !== []) {
            $options['json'] = $body;
        }
        if ($query !== []) {
            $options['query'] = $query;
        }
        $request = $this->request()->withToken($this->token);
        $response = $request->send($method, $this->url($path), $options);
        $payload = $this->decode($response);
        if ($response->status() === 401 && $retry) {
            $this->token = null;

            return $this->call($method, $path, $body, $query, false);
        }
        $this->assertSuccessful($response, $payload);

        return is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout($this->timeout);
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
    }

    private function decode(Response $response): array
    {
        $body = trim($response->body());
        if ($body === '') {
            return [];
        }
        $payload = json_decode($body, true);
        if (! is_array($payload)) {
            throw new RuntimeException('Openprovider returned invalid JSON', $response->status());
        }

        return $payload;
    }

    private function assertSuccessful(Response $response, array $payload): void
    {
        if ($response->successful() && (int) ($payload['code'] ?? 0) === 0) {
            return;
        }
        $parts = [];
        foreach (['desc', 'data', 'message', 'error', 'warnings'] as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }
            if (is_scalar($payload[$key])) {
                $parts[] = trim((string) $payload[$key]);
            } else {
                $this->collectErrorDetails($payload[$key], $parts, $key);
            }
        }
        $message = implode(': ', array_values(array_unique(array_filter($parts))));

        throw new RuntimeException(mb_substr($message ?: 'Openprovider request failed', 0, 4000), $response->status() ?: (int) ($payload['code'] ?? 0));
    }

    private function collectErrorDetails(mixed $value, array &$parts, string $path): void
    {
        if (is_scalar($value)) {
            $value = trim((string) $value);
            if ($value !== '') {
                $parts[] = $path.': '.$value;
            }

            return;
        }
        if (! is_array($value)) {
            return;
        }
        foreach ($value as $key => $child) {
            if (in_array(strtolower((string) $key), ['access_token', 'token', 'password', 'credentials'], true)) {
                continue;
            }
            $this->collectErrorDetails($child, $parts, $path.'.'.$key);
        }
    }
}
