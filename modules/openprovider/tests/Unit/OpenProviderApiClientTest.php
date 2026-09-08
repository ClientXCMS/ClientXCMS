<?php

namespace App\Modules\OpenProvider\Tests\Unit;

require_once dirname(__DIR__, 2).'/src/OpenProviderApiClient.php';

use App\Modules\OpenProvider\OpenProviderApiClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenProviderApiClientTest extends TestCase
{
    public function test_it_authenticates_and_sends_the_bearer_token(): void
    {
        Http::fake([
            'https://api.openprovider.test/v1beta/auth/login' => Http::response(['code' => 0, 'data' => ['token' => 'first-token', 'reseller_id' => 42]], 200),
            'https://api.openprovider.test/v1beta/domains/check' => Http::response(['code' => 0, 'data' => ['results' => []]], 200),
        ]);

        $client = new OpenProviderApiClient('user', 'secret', 'https://api.openprovider.test');
        $this->assertSame([], $client->checkDomain(['name' => 'example', 'extension' => 'com'])['results']);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.openprovider.test/v1beta/domains/check'
            && $request->hasHeader('Authorization', 'Bearer first-token'));
    }

    public function test_login_does_not_bind_token_to_local_server_address(): void
    {
        request()->server->set('SERVER_ADDR', '172.18.0.2');
        Http::fake(['*/v1beta/auth/login' => Http::response(['code' => 0, 'data' => ['token' => 'token']])]);

        (new OpenProviderApiClient('user', 'secret'))->login();

        Http::assertSent(fn ($request) => $request['username'] === 'user'
            && $request['password'] === 'secret'
            && ! array_key_exists('ip', $request->data()));
    }

    public function test_login_preserves_explicit_ip(): void
    {
        Http::fake(['*/v1beta/auth/login' => Http::response(['code' => 0, 'data' => ['token' => 'token']])]);

        (new OpenProviderApiClient('user', 'secret', 'https://api.openprovider.test', '203.0.113.10'))->login();

        Http::assertSent(fn ($request) => $request['ip'] === '203.0.113.10');
    }

    public function test_it_reauthenticates_once_after_unauthorized_response(): void
    {
        Http::fake([
            'https://api.openprovider.test/v1beta/auth/login' => Http::sequence()
                ->push(['code' => 0, 'data' => ['token' => 'expired']], 200)
                ->push(['code' => 0, 'data' => ['token' => 'fresh']], 200),
            'https://api.openprovider.test/v1beta/domains/check' => Http::sequence()
                ->push(['code' => 401, 'desc' => 'Expired'], 401)
                ->push(['code' => 0, 'data' => ['results' => [['status' => 'free']]]], 200),
        ]);

        $result = (new OpenProviderApiClient('user', 'secret', 'https://api.openprovider.test'))
            ->checkDomain(['name' => 'example', 'extension' => 'com']);

        $this->assertSame('free', $result['results'][0]['status']);
        Http::assertSentCount(4);
    }

    public function test_it_surfaces_openprovider_business_errors(): void
    {
        Http::fake([
            '*/v1beta/auth/login' => Http::response(['code' => 0, 'data' => ['token' => 'token']], 200),
            '*/v1beta/domains/check' => Http::response(['code' => 196, 'desc' => 'Invalid domain', 'data' => 'Registry rejected it'], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid domain: Registry rejected it');
        (new OpenProviderApiClient('user', 'secret'))->checkDomain(['name' => 'bad', 'extension' => 'test']);
    }

    public function test_it_surfaces_nested_registry_details_without_exposing_tokens(): void
    {
        Http::fake([
            '*/v1beta/auth/login' => Http::response(['code' => 0, 'data' => ['token' => 'safe-session-token']], 200),
            '*/v1beta/domains' => Http::response([
                'code' => 422,
                'desc' => 'Invalid request',
                'data' => [
                    'registry' => ['message' => 'WPP contract is not signed', 'field' => 'owner_handle'],
                    'token' => 'must-not-be-visible',
                ],
            ], 422),
        ]);

        try {
            (new OpenProviderApiClient('user', 'secret'))->createDomain([]);
            $this->fail('An exception was expected');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('WPP contract is not signed', $exception->getMessage());
            $this->assertStringContainsString('owner_handle', $exception->getMessage());
            $this->assertStringNotContainsString('must-not-be-visible', $exception->getMessage());
        }
    }

    public function test_it_rejects_invalid_json(): void
    {
        Http::fake(['*/v1beta/auth/login' => Http::response('not-json', 200)]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid JSON');
        (new OpenProviderApiClient('user', 'secret'))->login();
    }

    public function test_it_does_not_retry_unauthorized_response_twice(): void
    {
        Http::fake([
            '*/v1beta/auth/login' => Http::sequence()->push(['code' => 0, 'data' => ['token' => 'one']], 200)->push(['code' => 0, 'data' => ['token' => 'two']], 200),
            '*/v1beta/domains/check' => Http::response(['code' => 401, 'desc' => 'Unauthorized'], 401),
        ]);
        try {
            (new OpenProviderApiClient('user', 'secret'))->checkDomain(['name' => 'example', 'extension' => 'com']);
            $this->fail('An exception was expected');
        } catch (RuntimeException $e) {
            $this->assertSame(401, $e->getCode());
            Http::assertSentCount(4);
        }
    }
}
