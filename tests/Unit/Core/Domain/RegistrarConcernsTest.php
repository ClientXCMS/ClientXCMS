<?php

namespace Tests\Unit\Core\Domain;

use App\Core\Domain\Concerns\HandlesDnsRecordIds;
use App\Core\Domain\Concerns\ParsesRegistrarDates;
use App\Core\Domain\Concerns\ReadsDomainService;
use App\Core\Domain\Concerns\ResolvesRegistrarServer;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use RuntimeException;
use Tests\TestCase;

class RegistrarConcernsTest extends TestCase
{
    public function test_record_ids_round_trip(): void
    {
        $record = ['name' => 'www', 'type' => 'A', 'value' => '192.0.2.1', 'ttl' => 3600];
        $concerns = $this->concerns();

        $this->assertSame($record, $concerns->call('decodeRecordId', $concerns->call('recordId', $record)));
    }

    public function test_malformed_record_ids_are_rejected(): void
    {
        $concerns = $this->concerns();
        foreach (['not-a-record', '', rtrim(base64_encode('{"name":"www"}'), '=')] as $id) {
            try {
                $concerns->call('decodeRecordId', $id);
                $this->fail("Record id '{$id}' should have been rejected");
            } catch (RuntimeException $e) {
                $this->assertSame('Invalid DNS record identifier', $e->getMessage());
            }
        }
    }

    public function test_server_params_expose_hidden_credentials(): void
    {
        $server = new Server(['hostname' => 'concerns', 'username' => 'user', 'password' => 'secret']);

        $params = $this->concerns()->call('serverParams', $server, ['test_mode' => true]);

        $this->assertSame('user', $params['username']);
        $this->assertSame('secret', $params['password']);
        $this->assertSame('concerns', $params['hostname']);
        $this->assertTrue($params['test_mode']);
    }

    public function test_domain_is_normalized_and_split_on_the_last_dot(): void
    {
        $service = new Service;
        $service->name = ' Example.CO.uk. ';
        $concerns = $this->concerns();

        $this->assertSame('example.co.uk', $concerns->call('domain', $service));
        $this->assertSame(['example.co', 'uk'], $concerns->call('splitDomain', 'example.co.uk'));
        $this->assertSame('uk', $concerns->call('extension', $service));
    }

    public function test_stored_tld_wins_over_the_last_label(): void
    {
        $service = new Service;
        $service->name = 'example.co.uk';
        $service->data = ['tld' => '.CO.UK'];

        $this->assertSame('co.uk', $this->concerns()->call('extension', $service));
    }

    public function test_invalid_domains_are_rejected(): void
    {
        $concerns = $this->concerns();
        foreach (['', 'localhost', '.com'] as $domain) {
            try {
                $concerns->call('normalizeDomain', $domain);
                $this->fail("Domain '{$domain}' should have been rejected");
            } catch (RuntimeException $e) {
                $this->assertSame('Invalid domain name', $e->getMessage());
            }
        }
    }

    public function test_service_flags_prefer_service_data(): void
    {
        $service = new Service;
        $service->name = 'example.com';
        $service->data = ['dns_management' => true, 'whois_privacy' => false];
        $concerns = $this->concerns();

        $this->assertTrue($concerns->call('dnsManagement', $service));
        $this->assertFalse($concerns->call('whoisPrivacy', $service));
    }

    public function test_years_are_rounded_up_and_capped(): void
    {
        $service = new Service;
        $concerns = $this->concerns();
        foreach (['monthly' => 1, 'annually' => 1, 'biennially' => 2, 'triennially' => 3] as $billing => $years) {
            $service->billing = $billing;
            $this->assertSame($years, $concerns->call('years', $service), $billing);
        }
    }

    public function test_registrar_dates_are_parsed_or_ignored(): void
    {
        $concerns = $this->concerns();

        $this->assertSame('2026-10-24', $concerns->call('dateString', '2026/10/24'));
        $this->assertSame('2026-10-24', $concerns->call('dateString', '2026-10-24 T10:00:00'));
        $this->assertSame('2026-10-24', $concerns->call('dateString', '2026-10-24T10:00:00Z'));
        $this->assertNull($concerns->call('dateString', 'not a date'));
        $this->assertNull($concerns->call('dateString', null));
        $this->assertNull($concerns->call('dateString', ''));
    }

    private function concerns(): object
    {
        return new class
        {
            use HandlesDnsRecordIds, ParsesRegistrarDates, ReadsDomainService, ResolvesRegistrarServer;

            public function uuid(): string
            {
                return 'concerns';
            }

            public function call(string $method, mixed ...$arguments): mixed
            {
                return $this->{$method}(...$arguments);
            }
        };
    }
}
