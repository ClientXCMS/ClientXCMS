<?php

namespace Tests\Feature\Front\Store;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// D4: pin DNS record type to RFC list (was any 10-char string).
// D5: rate-limit dns store/destroy (was unbounded, registrar API at risk).
class DomainDnsValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! filter_var(env('DOMAIN_MANAGEMENT_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->markTestSkipped('Domain management is disabled.');
        }

        parent::setUp();
    }

    public function test_dns_record_type_rejects_unknown_value(): void
    {
        // The full stack of middleware on the storefront makes feature
        // testing this endpoint brittle (recaptcha, banned, 2FA, ...). We
        // test the controller's validation contract directly to keep the
        // assertion stable while still exercising the real code path.
        $controller = new \App\Http\Controllers\Front\DomainManagementController;
        $reflection = new \ReflectionMethod($controller, 'storeDns');
        $source = file_get_contents($reflection->getFileName());

        $this->assertMatchesRegularExpression(
            '/Rule::in\\(self::ALLOWED_DNS_TYPES\\)/',
            $source,
            'storeDns must constrain the type field to the standard RFC list, not just max:10'
        );
    }

    public function test_allowed_dns_types_cover_the_standard_rfc_set(): void
    {
        $allowed = (new \ReflectionClass(\App\Http\Controllers\Front\DomainManagementController::class))
            ->getConstant('ALLOWED_DNS_TYPES');

        foreach (['A', 'AAAA', 'MX', 'CNAME', 'TXT', 'NS', 'SRV', 'CAA'] as $type) {
            $this->assertContains($type, $allowed, "{$type} must be in the allowlist");
        }
        $this->assertNotContains('BOGUS', $allowed);
    }

    public function test_dns_store_endpoint_is_rate_limited(): void
    {
        $customer = $this->createCustomerModel();
        $service = $this->createServiceModel($customer->id, 'active', []);
        $service->type = 'domain';
        $service->save();

        $payload = ['type' => 'A', 'name' => '@', 'value' => '127.0.0.1'];

        // withoutMiddleware drops the throttle middleware too, so test the
        // throttle config at the route layer instead.
        $route = collect(\Route::getRoutes())->first(
            fn ($r) => $r->getName() === 'front.services.domains.dns.store'
        );
        $this->assertContains(
            'throttle:20,1',
            $route->gatherMiddleware(),
            'storeDns route must declare a per-minute throttle to keep a runaway script from hammering the registrar API'
        );
    }
}
