<?php

namespace Tests\Feature\Front\Store;

use App\Core\Domain\FakeDomainRegistrar;
use App\DTO\Domain\DomainAvailabilityDTO;
use App\Models\Store\DomainTld;
use App\Models\Store\DomainTldPrice;
use App\Services\Domain\DomainRegistrarManager;
use App\Services\Store\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainSearchProgressiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! filter_var(env('DOMAIN_MANAGEMENT_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->markTestSkipped('Domain management is disabled.');
        }
        parent::setUp();
        app(CurrencyService::class)->setCurrency('USD');
        $product = $this->createProductModel('active', 10);
        $product->type = 'domain';
        $product->save();
        foreach (['.com', '.net', '.org', '.app', '.dev', '.io'] as $extension) {
            $tld = DomainTld::create(['extension' => $extension, 'status' => 'active']);
            DomainTldPrice::create(['domain_tld_id' => $tld->id, 'currency' => 'USD', 'action' => 'register', 'billing' => 'annually', 'price' => 10, 'setup' => 0]);
        }
    }

    public function test_ajax_prepares_cards_without_checks_then_checks_at_most_five_per_request(): void
    {
        $registrar = new RecordingDomainRegistrar;
        app(DomainRegistrarManager::class)->register($registrar);

        $prepared = $this->postJson(route('front.store.domains.search'), ['domain' => 'example.com']);
        $prepared->assertOk()->assertJsonCount(2, 'batches');
        $this->assertSame([], $registrar->calls);
        $this->assertStringContainsString('Vérification en cours', $prepared->json('html'));
        $this->assertStringNotContainsString('href=', $prepared->json('html'));
        $this->assertSame('example.com', $prepared->json('batches.0.domains.0'));

        $first = $this->postJson(route('front.store.domains.check'), ['domain' => 'example.com', 'batch' => 0]);
        $first->assertOk();
        $first->assertJson(['failed' => []]);
        $this->assertCount(5, $registrar->calls[0]);
        $this->assertCount(5, $first->json('cards'));
        $this->assertStringContainsString('Disponible', $first->json('cards')['example.com']);

        $second = $this->postJson(route('front.store.domains.check'), ['domain' => 'example.com', 'batch' => 1]);
        $second->assertOk();
        $this->assertCount(1, $registrar->calls[1]);
        $this->postJson(route('front.store.domains.check'), ['domain' => 'example.com', 'batch' => 2])->assertStatus(422);
    }

    public function test_partial_checks_fail_closed_and_post_fallback_still_returns_results(): void
    {
        $registrar = new RecordingDomainRegistrar;
        $registrar->partial = true;
        app(DomainRegistrarManager::class)->register($registrar);

        $batch = $this->postJson(route('front.store.domains.check'), ['domain' => 'taken.com', 'batch' => 0]);
        $batch->assertOk();
        $this->assertContains('taken.app', $batch->json('failed'));
        $this->assertStringContainsString('Indisponible', $batch->json('cards')['taken.com']);
        $this->assertStringContainsString('La disponibilité n’a pas pu être vérifiée', $batch->json('cards')['taken.app']);
        $this->assertStringNotContainsString('href=', $batch->json('cards')['taken.app']);

        $fallback = $this->post(route('front.store.domains.search'), ['domain' => 'example.com']);
        $fallback->assertOk()->assertSee('example.com');
        $this->assertCount(3, $registrar->calls);
    }
}

class RecordingDomainRegistrar extends FakeDomainRegistrar
{
    public array $calls = [];
    public bool $partial = false;

    public function checkAvailabilityBatch(array $domains): array
    {
        $this->calls[] = $domains;
        if ($this->partial) {
            $domain = $domains[0];
            return [$domain => new DomainAvailabilityDTO($domain, false)];
        }

        return parent::checkAvailabilityBatch($domains);
    }
}
