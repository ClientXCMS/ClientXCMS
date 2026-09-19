<?php

namespace App\Http\Controllers\Admin\Provisioning;

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\Provisioning\Server;
use App\Models\Store\DomainTld;
use App\Services\Domain\DomainPricingService;
use App\Services\Domain\DomainRegistrarManager;
use App\Services\Store\RecurringService;
use Illuminate\Http\Request;

class DomainTldController extends AbstractCrudController
{
    protected array $relations = ['server', 'prices'];

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

    protected function getIndexParams($items, string $translatePrefix)
    {
        return parent::getIndexParams($items, $translatePrefix) + [
            'tlds' => DomainTld::orderBy('extension')->get(),
            'servers' => Server::where('type', 'domain')->get(),
            'registrars' => app(DomainRegistrarManager::class)->all(),
            'defaultCurrency' => setting('store_currency', 'EUR'),
        ];
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
        $tld = \DB::transaction(function () use ($data, $request) {
            $tld = new DomainTld($data);
            $tld->normalizeExtension();
            $tld->save();
            $this->syncPrices($tld, $request->input('prices', []));

            return $tld;
        });

        return $this->storeRedirect($tld);
    }

    public function update(Request $request, DomainTld $domain_tld)
    {
        $this->checkPermission('update', $domain_tld);
        $data = $this->validated($request, $domain_tld);
        \DB::transaction(function () use ($domain_tld, $data, $request) {
            $domain_tld->fill($data);
            $domain_tld->normalizeExtension();
            $domain_tld->save();
            $this->syncPrices($domain_tld, $request->input('prices', []));
        });

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
        $request->merge(['extension' => \App\Services\Domain\DomainPricingService::normalizeExtension((string) $request->input('extension'))]);
        $data = $request->validate([
            'extension' => 'required|string|max:32|unique:domain_tlds,extension,'.($tld?->id ?? 'NULL'),
            'status' => 'required|string|in:active,hidden,unreferenced',
            'server_id' => ['nullable', \Illuminate\Validation\Rule::exists('servers', 'id')->where('type', 'domain')],
            'default_nameservers' => 'sometimes|array',
            'default_nameserver_ips' => 'sometimes|array',
            'default_dns_records' => 'sometimes|array',
            'apply_default_dns' => 'nullable',
            'dns_management' => 'nullable',
            'whois_privacy' => 'nullable',
            'prices.*.*.*.price' => 'nullable|numeric|min:0',
            'prices.*.*.*.setup' => 'nullable|numeric|min:0',
        ]);
        $data['dns_management'] = $request->boolean('dns_management');
        $data['whois_privacy'] = $request->boolean('whois_privacy');

        $data['apply_default_dns'] = $request->has('defaults_present') ? $request->boolean('apply_default_dns') : (bool) $tld?->apply_default_dns;
        foreach (['default_nameservers', 'default_nameserver_ips', 'default_dns_records'] as $key) {
            $data[$key] = $request->has('defaults_present') ? $request->input($key, []) : ($tld?->$key ?? []);
        }
        unset($data['prices']);

        return app(\App\Services\Domain\DomainDefaultsService::class)->validate($data, Server::find($data['server_id'] ?? null));
    }

    private function formParams(DomainTld $item): array
    {
        $this->shareSettingsCard();

        $defaultCurrency = setting('store_currency', 'EUR');

        return [
            'item' => $item,
            'servers' => ['' => 'None'] + Server::where('type', 'domain')->pluck('name', 'id')->toArray(),
            'defaultCurrency' => $defaultCurrency,
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
        app(\App\Services\Domain\DomainTldPriceWriter::class)->write($tld, $prices);
    }

    private function shareSettingsCard(): void
    {
        $card = app('settings')->getCards()->firstWhere('uuid', 'provisioning');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'domain_tlds');
        \View::share('current_card', $card);
        \View::share('current_item', $item);
    }
}
