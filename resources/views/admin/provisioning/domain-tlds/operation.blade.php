@extends('admin.layouts.admin')
@section('title', __('provisioning.admin.domain_tlds.tools.tools'))
@section('content')
<div class="container mx-auto space-y-6">
    @include('admin.shared.alerts')
    <a class="btn btn-secondary mr-auto" href="{{ route('admin.domain_tlds.index') }}">{{ __('global.back') }}</a>
    <div class="card">
        <div class="card-heading">
            <div>
                <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __('provisioning.admin.domain_tlds.tools.state_'.$operation->status) }}</h1>
                @if(isset($operation->payload['environment']))
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($operation->payload['environment']) }}</p>
                @endif
            </div>
        </div>
        @if(in_array($operation->status, ['pending','loading']))
        <p>{{ $operation->progress }} % - {{ __('provisioning.admin.domain_tlds.tools.worker_help') }}</p>
        <progress class="w-full mt-4" max="100" value="{{ $operation->progress }}"></progress>
        <a class="btn btn-secondary mt-4" href="{{ request()->url() }}">{{ __('provisioning.admin.domain_tlds.tools.refresh') }}</a>
        <span data-catalog-poll></span>
        @elseif($operation->status === 'failed')
        <p class="text-red-600">{{ $operation->error }}</p>
        @elseif($operation->status === 'applied')
        <p>{{ __('provisioning.admin.domain_tlds.tools.applied') }} ({{ $operation->payload['applied_count'] ?? 0 }})</p>
        <button class="btn btn-primary" onclick="window.location.href='{{ route('admin.domain_tlds.index') }}'">{{ __('global.back') }}</button>
        @elseif($operation->status === 'ready')
        <p class="text-sm text-gray-500 mb-4">{{ __('provisioning.admin.domain_tlds.tools.catalog_help') }}</p>
        <form method="GET" class="flex gap-3 mb-4">
            <input class="input-text" name="q" value="{{ $query }}" placeholder="{{ __('provisioning.admin.domain_tlds.tools.filter') }}">
            <button class="btn btn-secondary">{{ __('provisioning.admin.domain_tlds.tools.filter') }}</button>
        </form>
        <form method="POST" action="{{ route('admin.domain_tlds.tools.preview', $operation) }}" data-catalog-selection="{{ $operation->id }}">
            @csrf
            <div class="border rounded-lg overflow-x-auto dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-start">
                                @include('shared/checkbox', ['name' => 'select_page', 'value' => 'true', 'label' => null, 'attributes' => ['data-select-page' => true, 'aria-label' => __('provisioning.admin.domain_tlds.tools.select_visible')]])
                                <input type="checkbox" data-select-page aria-label="{{ __('provisioning.admin.domain_tlds.tools.select_visible') }}" class="sr-only">
                            </th>
                            <th class="px-6 py-3 text-start">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">TLD</span>
                            </th>
                            @foreach(['register', 'renew', 'transfer'] as $action)
                            <th class="px-6 py-3 text-start">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                    {{ __('provisioning.domain_manager.'.$action) }}
                                </span>
                            </th>
                            @endforeach
                            <th class="px-6 py-3 text-start">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.status') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($rows as $extension => $group)
                        <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                            <td class="h-px w-px whitespace-nowrap px-6 py-3">
                                @include('shared/checkbox', ['name' => 'selection[]', 'value' => $extension, 'label' => null, 'attributes' => ['data-catalog-tld' => true, 'aria-label' => $extension]])
                                <input type="checkbox" data-catalog-tld value="{{ $extension }}" aria-label="{{ $extension }}" class="sr-only">
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-200">{{ $extension }}</td>
                            @foreach(['register', 'renew', 'transfer'] as $action)
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                @forelse($group->where('action', $action) as $row)
                                <div>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ formatted_price($row['cost'], $row['currency']) }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-500">{{ __('recurring.'.$row['billing']) }}</span>
                                </div>
                                @empty
                                <span class="text-gray-400">-</span>
                                @endforelse
                            </td>
                            @endforeach
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $tlds->contains('extension', $extension) ? __('provisioning.admin.domain_tlds.tools.existing') : __('provisioning.admin.domain_tlds.tools.new') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="py-1 px-4 mx-auto">{{ $rows->links('admin.shared.layouts.pagination') }}</div>
            <p class="my-3"><span data-selection-count>0</span> {{ __('provisioning.admin.domain_tlds.tools.selected') }}</p>
            <div data-selected-inputs></div>
            <div class="grid md:grid-cols-3 gap-4 my-5">
                @foreach(['register','renew','transfer'] as $action)
                <fieldset class="border rounded-xl p-4 dark:border-gray-700">
                    <legend>{{ __('provisioning.domain_manager.'.$action) }}</legend>
                    <label>{{ __('provisioning.admin.domain_tlds.tools.markup') }} (%)<input class="input-text" name="rules[{{ $action }}][percentage]" type="number" step="0.01" min="0" value="{{ old('rules.'.$action.'.percentage', 0) }}" required></label>
                    <label>{{ __('provisioning.admin.domain_tlds.tools.fixed') }} ({{ $currency }})<input class="input-text" name="rules[{{ $action }}][fixed]" type="number" step="0.01" min="0" value="{{ old('rules.'.$action.'.fixed', 0) }}" required></label>
                </fieldset>
                @endforeach
            </div>
            @foreach($currencies as $from)
            @if(strtoupper($from) !== strtoupper($currency))<label class="block mb-3">1 {{ $from }} = <input class="input-text" name="rates[{{ $from }}]" type="number" step="0.000001" min="0.000001" value="{{ old('rates.'.$from) }}" required> {{ $currency }}</label>@endif
            @endforeach
            <label class="block my-3">{{ __('provisioning.admin.domain_tlds.tools.source') }}<select class="input-text" name="source_id">
                    <option value="">-</option>@foreach($tlds as $tld)<option value="{{ $tld->id }}">{{ $tld->extension }}</option>@endforeach
                </select></label>
                <div class="grid md:grid-rows-2 gap-4 my-5">
                @include('admin/shared/checkbox', ['name' => 'update_existing', 'label' => __('provisioning.admin.domain_tlds.tools.update_existing'), 'type' => 'checkbox', 'value' => 1, 'checked' => old('update_existing', false)])
                @include('admin/shared/checkbox', ['name' => 'activate', 'label' => __('provisioning.admin.domain_tlds.tools.activate'), 'type' => 'checkbox', 'value' => 1, 'checked' => old('activate', false)])
</div>
                <button class="btn btn-primary">{{ __('provisioning.admin.domain_tlds.tools.preview') }}</button>
        </form>
        @elseif($operation->status === 'preview')
        @php
            $targets = $operation->payload['targets'] ?? [];
            $previewCurrency = $operation->payload['currency'] ?? null;
        @endphp
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-700">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('provisioning.admin.domain_tlds.tools.preview') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ count($targets) }} {{ __('provisioning.admin.domain_tlds.tools.selected') }}</p>
            </div>
            @if($previewCurrency)<span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-slate-800 dark:text-gray-300">{{ $previewCurrency }}</span>@endif
        </div>
        <form method="POST" action="{{ route('admin.domain_tlds.tools.apply', $operation) }}" data-domain-apply>
            @csrf
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                @foreach($targets as $target)
                    @php
                        $previousPrices = $target['previous_prices'] ?? [];
                        $newPrices = $target['prices'] ?? [];
                        $visibleSettings = collect($target['settings'] ?? [])->except(['default_nameservers', 'default_nameserver_ips', 'default_dns_records']);
                    @endphp
                    <section class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-slate-900" aria-labelledby="preview-target-{{ $loop->index }}">
                        <div class="flex items-center justify-between gap-2 border-b border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-slate-800/60">
                            <h3 id="preview-target-{{ $loop->index }}" class="truncate font-semibold text-gray-900 dark:text-white">{{ $target['extension'] }}</h3>
                            <span class="shrink-0 rounded-full border border-gray-200 px-2 py-0.5 text-[11px] text-gray-500 dark:border-gray-600 dark:text-gray-400">{{ $target['before'] === null ? __('provisioning.admin.domain_tlds.tools.new') : __('provisioning.admin.domain_tlds.tools.existing') }}</span>
                        </div>
                        <div class="space-y-3 p-4">
                            @if($visibleSettings->isNotEmpty())
                                <dl class="space-y-1.5 text-xs">
                                    @foreach($visibleSettings as $field => $value)
                                        @php
                                            $label = __('provisioning.admin.domain_tlds.tools.field_'.$field);
                                            $before = $target['previous'][$field] ?? null;
                                            $format = fn ($item) => is_bool($item) ? ($item ? '✓' : '-') : ($item ?? '-');
                                        @endphp
                                        <div class="flex items-start justify-between gap-2"><dt class="text-gray-500 dark:text-gray-400">{{ $label === 'provisioning.admin.domain_tlds.tools.field_'.$field ? str_replace('_', ' ', ucfirst($field)) : $label }}</dt><dd class="max-w-[55%] truncate text-right font-medium text-gray-800 dark:text-gray-200" title="{{ $format($before) }} → {{ $format($value) }}">{{ $format($before) }} → {{ $format($value) }}</dd></div>
                                    @endforeach
                                </dl>
                            @endif
                            @if($operation->kind === 'copy' && $newPrices)
                                <div class="border-t border-gray-100 pt-3 text-xs dark:border-gray-700 grid grid-cols-3 gap-2">
                                    @foreach($newPrices as $priceCurrency => $actions)
                                        @foreach($actions as $action => $billings)
                                            @foreach($billings as $billing => $price)
                                                @php
                                                    $previousPrice = $previousPrices[$priceCurrency][$action][$billing]['price'] ?? null;
                                                @endphp
                                                <div class="flex items-start justify-between gap-2"><span class="text-gray-500 dark:text-gray-400">{{ __('provisioning.domain_manager.'.$action) }} · {{ __('recurring.'.$billing) }}</span><span class="shrink-0 text-right font-semibold text-gray-900 dark:text-white">{{ $previousPrice !== null ? formatted_price($previousPrice, $priceCurrency).' → ' : '' }}{{ formatted_price($price['price'], $priceCurrency) }}@if(!empty($price['setup']))<small class="block font-normal text-gray-500 dark:text-gray-400">+ {{ formatted_price($price['setup'], $priceCurrency) }} {{ __('store.setup_price') }}</small>@endif</span></div>
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                </div>
                            @endif
                            @if(!empty($target['price_rows']))
                                <div class="border-t border-gray-100 pt-3 text-xs dark:border-gray-700  grid grid-cols-3 gap-2">
                                    @foreach($target['price_rows'] as $row)
                                        @php
                                            $beforePrice = $previousPrices[$previewCurrency][$row['action']][$row['billing']]['price'] ?? null;
                                        @endphp
                                        <label class="block text-xs text-gray-600 dark:text-gray-300">
                                            <span class="mb-1 flex items-center justify-between gap-2"><span class="font-medium">{{ __('provisioning.domain_manager.'.$row['action']) }} · {{ __('recurring.'.$row['billing']) }}</span><span class="text-gray-400">{{ $beforePrice !== null ? __('provisioning.admin.domain_tlds.tools.before').' : '.formatted_price($beforePrice, $previewCurrency) : '' }}</span></span>
                                            <input class="input-text w-full" type="number" min="0" max="99999999" step="0.01" name="prices[{{ $row['key'] }}]" value="{{ old('prices.'.$row['key'], $row['selling']) }}" aria-label="{{ $target['extension'] }} {{ __('provisioning.domain_manager.'.$row['action']) }} {{ __('recurring.'.$row['billing']) }} {{ __('provisioning.admin.domain_tlds.tools.selling') }}" required>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($targets) }} {{ __('provisioning.admin.domain_tlds.tools.selected') }}</span>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-gray-200">{{ __('provisioning.admin.domain_tlds.tools.confirm') }}</button>
            </div>
        </form>
        @endif
    </div>
</div>
<script src="{{ Vite::asset('resources/global/js/admin/domain-tlds.js') }}" type="module"></script>
@endsection
