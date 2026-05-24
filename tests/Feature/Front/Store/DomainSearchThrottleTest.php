<?php

namespace Tests\Feature\Front\Store;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * D2 of the Domain TLD audit. /store/domains/search fans out one
 * registrar lookup per active TLD - 50 TLDs configured means 50
 * WHOIS/API calls per search hit. Without a cap, an attacker can
 * use the platform as a low-cost DDoS amplifier against the upstream
 * registrar APIs, blow through any per-month quota, and on a paid
 * provider rack up real bills.
 *
 * The cap is per-IP. Legitimate users typing a domain into the search
 * stay well under it; bots and abuse don't.
 */
class DomainSearchThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_search_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $response = $this->post(route('front.store.domains.search'), ['domain' => "test{$i}.com"]);
            $this->assertContains(
                $response->status(),
                [200, 302],
                "Search hit #{$i} must not be rate-limited yet (got HTTP {$response->status()})"
            );
        }

        $response = $this->post(route('front.store.domains.search'), ['domain' => 'flood.com']);

        $this->assertSame(
            429,
            $response->status(),
            'Search must trip the cap once an abuser blows through a sane per-minute budget - each hit triggers N upstream registrar lookups'
        );
    }
}
