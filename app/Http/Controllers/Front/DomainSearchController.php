<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Store\DomainTld;
use App\Models\Store\Product;
use App\Services\Domain\DomainPricingService;
use App\Services\Domain\DomainRegistrarManager;
use Illuminate\Http\Request;

class DomainSearchController extends Controller
{
    public function index(Request $request)
    {
        abort_if(! setting('domain_search_enabled', true), 404);

        return view('front.store.domains.index', [
            'query' => $request->query('domain'),
            'results' => collect(),
            'product' => $this->domainProduct(),
        ]);
    }

    public function search(Request $request)
    {
        abort_if(! setting('domain_search_enabled', true), 404);
        $validated = $request->validate([
            'domain' => 'required|string|max:253',
        ]);
        $name = strtolower(trim($validated['domain']));
        $base = explode('.', $name)[0];
        $product = $this->domainProduct();
        $registrars = app(DomainRegistrarManager::class);
        $pricing = app(DomainPricingService::class);

        $results = DomainTld::where('status', 'active')->orderBy('extension')->get()->map(function (DomainTld $tld) use ($base, $registrars, $pricing) {
            $domain = $base . $tld->extension;
            $registrar = $registrars->fromServer($tld->server);
            $availability = $registrar->checkAvailability($domain);
            $prices = $pricing->availableForTld($tld->extension, currency());

            return compact('tld', 'domain', 'availability', 'prices');
        });

        return view('front.store.domains.index', [
            'query' => $name,
            'results' => $results,
            'product' => $product,
        ]);
    }

    private function domainProduct(): ?Product
    {
        return Product::where('type', 'domain')->where('status', 'active')->orderBy('sort_order')->first();
    }
}
