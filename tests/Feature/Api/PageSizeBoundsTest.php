<?php

namespace Tests\Feature\Api;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class PageSizeBoundsTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    private const RECORDS = 101;

    private const MAX_PAGE_SIZE = 100;

    public static function pageSizeCases(): iterable
    {
        $routes = [
            'application customers' => ['application.customers', 25],
            'application departments' => ['application.departments', 25],
            'client invoices' => ['client.invoices', 10],
            'client tickets' => ['client.tickets', 10],
            'client services' => ['client.services', 10],
        ];
        $queries = [
            'negative' => ['per_page=-1', null],
            'zero' => ['per_page=0', null],
            'not an integer' => ['per_page=abc', null],
            'array' => ['per_page[]=1', null],
            'too large' => ['per_page=100000', self::MAX_PAGE_SIZE],
            'valid' => ['per_page=7', 7],
        ];

        foreach ($routes as $routeName => [$route, $default]) {
            foreach ($queries as $queryName => [$query, $expected]) {
                yield $routeName.' '.$queryName => [$route, $query, $expected ?? $default];
            }
        }
    }

    #[DataProvider('pageSizeCases')]
    public function test_page_size_is_bounded(string $route, string $query, int $expectedCount): void
    {
        $response = $this->listWithQuery($route, $query);

        $response->assertOk();
        $this->assertCount($expectedCount, $response->json('data'));
    }

    private function listWithQuery(string $route, string $query): TestResponse
    {
        return match ($route) {
            'application.customers' => $this->listApplication('customers', $query, fn () => Customer::factory()->count(self::RECORDS)->create()),
            'application.departments' => $this->listApplication('departments', $query, fn () => SupportDepartment::factory()->count(self::RECORDS)->create()),
            'client.invoices' => $this->listClient('invoices', $query, fn (Customer $customer) => Invoice::factory()->count(self::RECORDS)->create(['customer_id' => $customer->id])),
            'client.tickets' => $this->listClient('tickets', $query, fn (Customer $customer) => SupportTicket::factory()->count(self::RECORDS)->create(['customer_id' => $customer->id])),
            'client.services' => $this->listClient('services', $query, function (Customer $customer) {
                for ($i = 0; $i < self::RECORDS; $i++) {
                    $this->createServiceModel($customer->id);
                }
            }),
        };
    }

    private function listApplication(string $resource, string $query, callable $seed): TestResponse
    {
        $seed();

        return $this->performAction('GET', 'api/application/'.$resource.'?'.$query, [$resource.':index']);
    }

    private function listClient(string $resource, string $query, callable $seed): TestResponse
    {
        $customer = Customer::factory()->create();
        $seed($customer);
        $token = $customer->createToken('client-api', ['*'])->plainTextToken;

        return $this->withToken($token)->getJson('/api/client/'.$resource.'?'.$query);
    }
}
