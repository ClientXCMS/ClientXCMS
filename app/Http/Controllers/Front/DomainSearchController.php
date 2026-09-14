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
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)*$/i'],
            'operation' => ['nullable', 'in:register,transfer'],
        ]);
        $name = strtolower(trim($validated['domain']));
        $operation = $validated['operation'] ?? 'register';
        $pricing = app(DomainPricingService::class);
        $registrars = app(DomainRegistrarManager::class);
        $product = $this->domainProduct();
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
        $availability = [];
        if ($operation === 'register') {
            foreach ($candidates->groupBy(fn (array $item) => $item['registrar']?->uuid() ?? 'missing') as $group) {
                $registrar = $group->first()['registrar'];
                $domains = $group->pluck('domain')->all();
                try {
                    $checked = $registrar->checkAvailabilityBatch($domains);
                    foreach ($domains as $domain) {
                        $availability[$domain] = ($checked[$domain] ?? null) instanceof DomainAvailabilityDTO
                            ? $checked[$domain] : new DomainAvailabilityDTO($domain, false, null, false);
                    }
                } catch (\Throwable $e) {
                    report($e);
                    foreach ($domains as $domain) {
                        $availability[$domain] = new DomainAvailabilityDTO($domain, false, null, false);
                    }
                }
            }
        }
        $results = $candidates->map(function (array $item) use ($availability, $operation) {
            $domain = $item['domain'];
            return ['domain' => $domain, 'tld' => $item['tld'], 'prices' => $item['prices'],
                'transfer_prices' => $item['transferPrices'],
                'can_transfer' => $item['registrar']?->supportsTransfer() && $item['transferPrices'] !== [],
                'availability' => $operation === 'transfer' ? new DomainAvailabilityDTO($domain, true) : $availability[$domain]];
        })->sort(function (array $a, array $b) {
            return $a['availability']->available === $b['availability']->available ? 0 : ($a['availability']->available ? -1 : 1);
        })->values();
        $view = view('front.store.domains.index', compact('results', 'product', 'operation') + ['query' => $name]);
        if ($request->expectsJson()) {
            return response()->json(['html' => view('front.store.domains.results', compact('results', 'product', 'operation'))->render()]);
        }

        return $view;
    }

    private function domainProduct(): ?Product
    {
        return Product::where('type', 'domain')->where('status', 'active')->orderBy('sort_order')->get()
            ->first(fn (Product $product) => $product->hasPricesForCurrency(currency()));
    }
}
