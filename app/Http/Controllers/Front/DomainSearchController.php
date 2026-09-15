<?php

namespace App\Http\Controllers\Front;

use App\DTO\Domain\DomainAvailabilityDTO;
use App\Http\Controllers\Controller;
use App\Models\Store\DomainTld;
use App\Models\Store\Product;
use App\Services\Domain\DomainPricingService;
use App\Services\Domain\DomainRegistrarManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DomainSearchController extends Controller
{
    public function index(Request $request)
    {
        abort_if(! setting('domain_search_enabled', true), 404);

        return view('front.store.domains.index', [
            'query' => $request->query('domain'), 'results' => collect(),
            'product' => $this->domainProduct(), 'operation' => 'register',
        ]);
    }

    public function search(Request $request)
    {
        abort_if(! setting('domain_search_enabled', true), 404);
        [$name, $operation, $candidates] = $this->candidates($request);
        $product = $this->domainProduct();
        if ($request->expectsJson()) {
            $results = $this->results($candidates, [], $operation);
            $batches = $operation === 'register' ? $this->batches($candidates) : [];

            return response()->json([
                'html' => view('front.store.domains.results', compact('results', 'product', 'operation'))->render(),
                'batches' => array_map(fn (array $batch, int $index) => [
                    'id' => $index, 'domains' => array_column($batch, 'domain'),
                ], $batches, array_keys($batches)),
                'domain' => $name,
            ]);
        }

        $availability = [];
        if ($operation === 'register') {
            foreach ($this->batches($candidates) as $batch) {
                $availability += $this->checkBatch($batch);
            }
        }
        $results = $this->results($candidates, $availability, $operation);

        return view('front.store.domains.index', compact('results', 'product', 'operation') + ['query' => $name]);
    }

    public function check(Request $request)
    {
        abort_if(! setting('domain_search_enabled', true), 404);
        [, $operation, $candidates] = $this->candidates($request);
        if ($operation !== 'register') {
            abort(422);
        }
        $validated = $request->validate(['batch' => ['required', 'integer', 'min:0']]);
        $batch = $this->batches($candidates)[(int) $validated['batch']] ?? null;
        abort_if($batch === null, 422);

        $availability = $this->checkBatch($batch);
        $product = $this->domainProduct();
        $cards = [];
        foreach ($batch as $item) {
            $result = $this->result($item, $availability[$item['domain']], $operation);
            $cards[$item['domain']] = view('front.store.domains.card', compact('result', 'product', 'operation') + [
                'featured' => $candidates->first()['domain'] === $item['domain'],
            ])->render();
        }

        return response()->json([
            'cards' => $cards,
            'failed' => array_keys(array_filter($availability, fn (DomainAvailabilityDTO $result) => ! $result->checked)),
        ]);
    }

    private function candidates(Request $request): array
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)*$/i'],
            'operation' => ['nullable', 'in:register,transfer'],
        ]);
        $name = strtolower(trim($validated['domain']));
        $operation = $validated['operation'] ?? 'register';
        $pricing = app(DomainPricingService::class);
        $registrars = app(DomainRegistrarManager::class);
        $tlds = DomainTld::where('status', 'active')->with('server')->get();
        $matched = $tlds->sortByDesc(fn (DomainTld $tld) => strlen($tld->extension))
            ->first(fn (DomainTld $tld) => str_ends_with($name, $tld->extension));
        if ($operation === 'transfer' && $matched === null) {
            throw ValidationException::withMessages(['domain' => __('provisioning.domain_manager.errors.invalid_tld')]);
        }
        $base = $matched ? substr($name, 0, -strlen($matched->extension)) : $name;
        if ($base === '' || str_contains($base, '.')) {
            throw ValidationException::withMessages(['domain' => __('provisioning.domain_manager.search.invalid_domain')]);
        }
        $ordered = $tlds->sortBy(fn (DomainTld $tld) => ($matched?->id === $tld->id ? '0' : '1').$tld->extension)->values();
        $candidates = $ordered->map(function (DomainTld $tld) use ($base, $operation, $pricing, $registrars) {
            $prices = $pricing->availableForTld($tld->extension, currency(), $operation);
            $transferPrices = $pricing->availableForTld($tld->extension, currency(), DomainPricingService::ACTION_TRANSFER);
            $registrar = $registrars->fromServer($tld->server);
            return compact('tld', 'prices', 'transferPrices', 'registrar') + ['domain' => $base.$tld->extension];
        })->filter(fn (array $item) => ($item['prices'] !== [] || $item['transferPrices'] !== []) && ($operation !== 'transfer' || ($item['prices'] !== [] && $item['registrar']?->supportsTransfer())));
        if ($operation === 'transfer') {
            $candidates = $candidates->filter(fn (array $item) => $item['tld']->id === $matched?->id);
        }
        return [$name, $operation, $candidates->values()];
    }

    private function batches($candidates): array
    {
        $batches = [];
        foreach ($candidates->groupBy(fn (array $item) => $item['registrar']?->uuid() ?? 'missing') as $group) {
            foreach ($group->chunk(5) as $chunk) {
                $batches[] = $chunk->values()->all();
            }
        }

        return $batches;
    }

    private function checkBatch(array $batch): array
    {
        $domains = array_column($batch, 'domain');
        $availability = [];
        try {
            $checked = $batch[0]['registrar']->checkAvailabilityBatch($domains);
        } catch (\Throwable $e) {
            report($e);
            $checked = [];
        }
        if (! is_array($checked)) {
            $checked = [];
        }
        foreach ($domains as $domain) {
            $availability[$domain] = ($checked[$domain] ?? null) instanceof DomainAvailabilityDTO && $checked[$domain]->domain === $domain
                ? $checked[$domain] : new DomainAvailabilityDTO($domain, false, null, false);
        }

        return $availability;
    }

    private function result(array $item, ?DomainAvailabilityDTO $availability, string $operation): array
    {
        return ['domain' => $item['domain'], 'tld' => $item['tld'], 'prices' => $item['prices'],
            'transfer_prices' => $item['transferPrices'],
            'can_transfer' => $item['registrar']?->supportsTransfer() && $item['transferPrices'] !== [],
            'availability' => $operation === 'transfer' ? new DomainAvailabilityDTO($item['domain'], true) : $availability];
    }

    private function results($candidates, array $availability, string $operation)
    {
        return $candidates->map(fn (array $item) => $this->result($item, $availability[$item['domain']] ?? null, $operation));
    }

    private function domainProduct(): ?Product
    {
        return Product::where('type', 'domain')->where('status', 'active')->orderBy('sort_order')->get()
            ->first(fn (Product $product) => $product->hasPricesForCurrency(currency()));
    }
}
