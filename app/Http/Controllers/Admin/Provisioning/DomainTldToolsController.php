<?php

namespace App\Http\Controllers\Admin\Provisioning;

use App\Http\Controllers\Controller;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Models\Store\DomainOperation;
use App\Models\Store\DomainTld;
use App\Services\Domain\DomainCatalogService;
use App\Services\Domain\DomainDefaultsService;
use App\Services\Domain\DomainTldBulkService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DomainTldToolsController extends Controller
{
    private function authorizeTools(): int
    {
        abort_unless(auth('admin')->user()?->can('admin.manage_domain_tlds'), 403);

        return (int) auth('admin')->id();
    }

    public function catalog(Request $request, DomainCatalogService $catalog)
    {
        $admin = $this->authorizeTools();
        $data = $request->validate([
            'server_id' => 'required|integer|exists:servers,id',
            'registrar' => 'required|string',
            'extensions' => ['required', 'string', 'max:2000'],
        ]);
        $extensions = preg_split('/[\s,;]+/', $data['extensions'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $extensions = collect($extensions)->map(fn ($extension) => strtolower(ltrim(trim($extension), '.')))->unique()->values()->all();
        validator(['extensions' => $extensions], [
            'extensions' => 'required|array|min:1|max:100',
            'extensions.*' => ['required', 'string', 'distinct', 'max:32', 'regex:/^[a-z0-9-]+(?:\.[a-z0-9-]+)*$/'],
        ])->validate();
        $server = Server::findOrFail($request->integer('server_id'));
        abort_unless($server->hostname === $request->input('registrar'), 422);
        $operation = $catalog->start($server, $admin, $extensions);

        return redirect()->route('admin.domain_tlds.tools.operation', $operation);
    }

    public function operation(Request $request, DomainOperation $operation)
    {
        $admin = $this->authorizeTools();
        abort_unless((int) $operation->admin_id === $admin, 403);
        if ($operation->status !== 'applied' && $operation->expires_at->isPast()) {
            abort(410, __('provisioning.admin.domain_tlds.tools.stale'));
        }
        $rows = collect($operation->payload['rows'] ?? [])->groupBy('extension');
        $search = strtolower(trim((string) $request->query('q', '')));
        if ($search !== '') {
            $rows = $rows->filter(fn ($group, $extension) => str_contains($extension, $search));
        }
        $page = max(1, (int) $request->query('page', 1));
        $paginator = new LengthAwarePaginator($rows->slice(($page - 1) * 25, 25), $rows->count(), 25, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('admin.provisioning.domain-tlds.operation', [
            'operation' => $operation,
            'rows' => $paginator,
            'query' => $search,
            'tlds' => DomainTld::orderBy('extension')->get(),
            'currencies' => collect($operation->payload['rows'] ?? [])->pluck('currency')->unique(),
            'currency' => setting('store_currency', 'EUR'),
        ]);
    }

    public function copy(Request $request, DomainTldBulkService $bulk)
    {
        $admin = $this->authorizeTools();
        $data = $request->validate([
            'source_id' => 'required|integer|exists:domain_tlds,id',
            'destinations' => 'required|array|min:1|max:2000',
            'destinations.*' => 'integer|distinct|exists:domain_tlds,id',
            'fields' => 'required|array|min:1',
            'fields.*' => [Rule::in([...DomainDefaultsService::FIELDS, 'prices'])],
        ]);
        $source = DomainTld::findOrFail($data['source_id']);
        $targets = [];
        foreach (DomainTld::whereIn('id', $data['destinations'])->where('id', '!=', $source->id)->get() as $target) {
            $settings = \Illuminate\Support\Arr::only($source->toArray(), array_intersect($data['fields'], DomainDefaultsService::FIELDS));
            $targets[] = ['extension' => $target->extension, 'before' => $bulk->fingerprint($target), 'previous' => \Illuminate\Support\Arr::only($target->toArray(), array_keys($settings)), 'settings' => $settings, 'prices' => in_array('prices', $data['fields']) ? $bulk->priceMap($source) : [], 'previous_prices' => in_array('prices', $data['fields']) ? $bulk->priceMap($target) : []];
        }
        if (! $targets) {
            throw ValidationException::withMessages(['destinations' => __('provisioning.admin.domain_tlds.tools.select_targets')]);
        }
        $operation = DomainOperation::create(['kind' => 'copy', 'admin_id' => $admin, 'status' => 'preview', 'payload' => ['targets' => $targets], 'expires_at' => now()->addMinutes(30)]);

        return redirect()->route('admin.domain_tlds.tools.operation', $operation);
    }

    public function previewImport(Request $request, DomainOperation $operation, DomainCatalogService $catalog, DomainTldBulkService $bulk)
    {
        $admin = $this->authorizeTools();
        abort_unless((int) $operation->admin_id === $admin && $operation->kind === 'catalog' && $operation->status === 'ready' && $operation->expires_at->isFuture(), 403);
        $data = $request->validate([
            'selected' => 'required|array|min:1|max:2000',
            'selected.*' => 'required|string|max:32|distinct',
            'source_id' => 'nullable|integer|exists:domain_tlds,id',
            'rules' => 'required|array',
            'rules.*.percentage' => 'required|numeric|min:0|max:10000',
            'rules.*.fixed' => 'required|numeric|min:0|max:99999999',
            'rates' => 'nullable|array',
            'rates.*' => 'required|numeric|gt:0|max:1000000',
            'update_existing' => 'nullable|boolean',
            'activate' => 'nullable|boolean',
        ]);
        $currency = setting('store_currency', 'EUR');
        $source = ! empty($data['source_id']) ? DomainTld::findOrFail($data['source_id']) : null;
        $groups = collect($operation->payload['rows'])->groupBy('extension');
        $targets = [];
        foreach ($data['selected'] as $extension) {
            if (! $groups->has($extension)) {
                throw ValidationException::withMessages(['selected' => __('provisioning.admin.domain_tlds.tools.stale')]);
            }
            $tld = DomainTld::where('extension', $extension)->first();
            if ($tld && ! $request->boolean('update_existing')) {
                continue;
            }
            $settings = $tld ? [] : ['server_id' => $operation->server_id, 'status' => $request->boolean('activate') ? 'active' : 'hidden', 'whois_privacy' => false];
            if (! $tld && $source) {
                $settings += \Illuminate\Support\Arr::only($source->toArray(), ['default_nameservers', 'default_nameserver_ips', 'default_dns_records', 'apply_default_dns', 'dns_management']);
            }
            $priceRows = [];
            foreach ($groups[$extension] as $row) {
                $rate = strtoupper($row['currency']) === strtoupper($currency) ? 1 : ($data['rates'][$row['currency']] ?? null);
                if ($rate === null) {
                    throw ValidationException::withMessages(['rates' => __('provisioning.admin.domain_tlds.tools.exchange_required')]);
                }
                $rule = $data['rules'][$row['action']] ?? null;
                if (! $rule) {
                    throw ValidationException::withMessages(['rules' => __('provisioning.admin.domain_tlds.tools.invalid_price')]);
                }
                $priceRows[] = $row + ['key' => hash('sha256', $extension.'/'.$row['action'].'/'.$row['billing'].'/'.$row['currency']), 'rate' => $rate, 'selling' => $catalog->sellingPrice((float) $row['cost'], (float) $rule['percentage'], (float) $rule['fixed'], (float) $rate)];
            }
            $targets[] = ['extension' => $extension, 'before' => $tld ? $bulk->fingerprint($tld) : null, 'previous' => $tld ? \Illuminate\Support\Arr::only($tld->toArray(), DomainDefaultsService::FIELDS) : [], 'settings' => $settings, 'prices' => [], 'previous_prices' => $tld ? $bulk->priceMap($tld) : [], 'price_rows' => $priceRows];
        }
        if (! $targets) {
            throw ValidationException::withMessages(['selected' => __('provisioning.admin.domain_tlds.tools.select_targets')]);
        }
        $preview = DomainOperation::create(['kind' => 'import', 'admin_id' => $admin, 'server_id' => $operation->server_id, 'connection_key' => $operation->connection_key, 'status' => 'preview', 'payload' => ['currency' => $currency, 'targets' => $targets, 'environment' => $operation->payload['environment']], 'expires_at' => now()->addMinutes(30)]);

        return redirect()->route('admin.domain_tlds.tools.operation', $preview);
    }

    public function apply(Request $request, DomainOperation $operation, DomainTldBulkService $bulk)
    {
        $admin = $this->authorizeTools();
        abort_unless((int) $operation->admin_id === $admin && in_array($operation->kind, ['copy', 'import']), 403);
        $data = $request->validate(['prices' => 'nullable|array', 'prices.*' => 'required|numeric|min:0|max:99999999']);
        $bulk->apply($operation, $data['prices'] ?? []);

        return redirect()->route('admin.domain_tlds.tools.operation', $operation)->with('success', __('provisioning.admin.domain_tlds.tools.applied'));
    }

    public function retryDns(Service $service)
    {
        $this->authorizeTools();
        abort_unless($service->type === 'domain' && ! empty($service->data['apply_default_dns']) && ! empty($service->data['registrar_id']), 422);
        \App\Jobs\Domain\InitializeDomainDns::dispatch($service->id)->afterCommit();

        return back()->with('success', __('provisioning.admin.domain_tlds.tools.queued'));
    }
}
