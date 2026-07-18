<?php

namespace App\Http\Controllers\Admin\Provisioning;

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\Provisioning\Server;
use App\Models\Store\DomainTld;
use App\Models\Store\DomainTldPrice;
use App\Services\Domain\DomainPricingService;
use App\Services\Store\CurrencyService;
use App\Services\Store\RecurringService;
use Illuminate\Http\Request;

class DomainTldController extends AbstractCrudController
{
    protected string $model = DomainTld::class;

    protected string $routePath = 'admin.domain_tlds';

    protected string $viewPath = 'admin.provisioning.domain-tlds';

    protected string $translatePrefix = 'provisioning.admin.domain_tlds';

    protected ?string $managedPermission = 'admin.manage_domain_tlds';

    public function index(Request $request)
    {
        $this->checkPermission('showAny');
        $this->shareSettingsCard();

        return parent::index($request);
    }

    public function create(Request $request)
    {
        $this->checkPermission('create');

        return $this->createView($this->formParams(new DomainTld));
    }

    public function show(DomainTld $domain_tld)
    {
        $this->checkPermission('show', $domain_tld);

        return $this->showView($this->formParams($domain_tld));
    }

    public function store(Request $request)
    {
        $this->checkPermission('create');
        $data = $this->validated($request);
        $tld = new DomainTld($data);
        $tld->normalizeExtension();
        $tld->save();
        $this->syncPrices($tld, $request->input('prices', []));

        return $this->storeRedirect($tld);
    }

    public function update(Request $request, DomainTld $domain_tld)
    {
        $this->checkPermission('update', $domain_tld);
        $data = $this->validated($request, $domain_tld);
        $domain_tld->fill($data);
        $domain_tld->normalizeExtension();
        $domain_tld->save();
        $this->syncPrices($domain_tld, $request->input('prices', []));

        return $this->updateRedirect($domain_tld);
    }

    public function destroy(DomainTld $domain_tld)
    {
        $this->checkPermission('delete', $domain_tld);
        $domain_tld->delete();

        return $this->deleteRedirect($domain_tld);
    }

    private function validated(Request $request, ?DomainTld $tld = null): array
    {
        $data = $request->validate([
            'extension' => 'required|string|max:32|unique:domain_tlds,extension,'.($tld?->id ?? 'NULL'),
            'status' => 'required|string|in:active,hidden,unreferenced',
            'server_id' => 'nullable|exists:servers,id',
            'dns_management' => 'nullable',
            'whois_privacy' => 'nullable',
            'prices.*.*.*.price' => 'nullable|numeric|min:0',
            'prices.*.*.*.setup' => 'nullable|numeric|min:0',
        ]);
        $data['dns_management'] = $request->boolean('dns_management');
        $data['whois_privacy'] = $request->boolean('whois_privacy');

        return $data;
    }

    private function formParams(DomainTld $item): array
    {
        $this->shareSettingsCard();

        return [
            'item' => $item,
            'servers' => ['' => 'None'] + Server::where('type', 'domain')->pluck('name', 'id')->toArray(),
            'currencies' => app(CurrencyService::class)->getCurrenciesKeys(),
            'recurrings' => collect(app(RecurringService::class)->getRecurrings())->only(['annually', 'biennially', 'triennially']),
            'actions' => [
                DomainPricingService::ACTION_REGISTER => __('provisioning.domain_manager.register'),
                DomainPricingService::ACTION_RENEW => __('provisioning.domain_manager.renew'),
                DomainPricingService::ACTION_TRANSFER => __('provisioning.domain_manager.transfer'),
            ],
            'prices' => $item->exists ? $item->prices->groupBy(['currency', 'action', 'billing']) : collect(),
        ];
    }

    private function syncPrices(DomainTld $tld, array $prices): void
    {
        $allowedCurrencies = app(\App\Services\Store\CurrencyService::class)->getCurrenciesKeys();
        $allowedActions = [
            \App\Services\Domain\DomainPricingService::ACTION_REGISTER,
            \App\Services\Domain\DomainPricingService::ACTION_RENEW,
            \App\Services\Domain\DomainPricingService::ACTION_TRANSFER,
        ];
        $allowedBillings = ['annually', 'biennially', 'triennially'];

        $tld->prices()->delete();
        foreach ($prices as $currency => $actions) {
            if (! in_array($currency, $allowedCurrencies, true) || ! is_array($actions)) {
                continue;
            }
            foreach ($actions as $action => $billings) {
                if (! in_array($action, $allowedActions, true) || ! is_array($billings)) {
                    continue;
                }
                foreach ($billings as $billing => $price) {
                    if (! in_array($billing, $allowedBillings, true) || ! is_array($price)) {
                        continue;
                    }
                    if (($price['price'] ?? null) === null && ($price['setup'] ?? null) === null) {
                        continue;
                    }
                    DomainTldPrice::create([
                        'domain_tld_id' => $tld->id,
                        'currency' => $currency,
                        'action' => $action,
                        'billing' => $billing,
                        'price' => $price['price'] ?? 0,
                        'setup' => $price['setup'] ?? 0,
                    ]);
                }
            }
        }
    }

    private function shareSettingsCard(): void
    {
        $card = app('settings')->getCards()->firstWhere('uuid', 'provisioning');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'subdomains_hosts');
        \View::share('current_card', $card);
        \View::share('current_item', $item);
    }
}
