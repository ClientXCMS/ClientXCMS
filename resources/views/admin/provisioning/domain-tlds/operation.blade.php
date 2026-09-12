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
                                <input type="checkbox" data-select-page aria-label="{{ __('provisioning.admin.domain_tlds.tools.select_visible') }}">
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
                                <input type="checkbox" data-catalog-tld value="{{ $extension }}" aria-label="{{ $extension }}">
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
            <label class="block mb-3"><input type="checkbox" name="update_existing" value="1"> {{ __('provisioning.admin.domain_tlds.tools.update_existing') }}</label>
            <label class="block mb-3"><input type="checkbox" name="activate" value="1"> {{ __('provisioning.admin.domain_tlds.tools.activate') }}</label>
            <button class="btn btn-primary">{{ __('provisioning.admin.domain_tlds.tools.preview') }}</button>
        </form>
        @elseif($operation->status === 'preview')
        <form method="POST" action="{{ route('admin.domain_tlds.tools.apply', $operation) }}" data-domain-apply>
            @csrf
            @foreach($operation->payload['targets'] as $target)
            <section class="rounded-xl border dark:border-gray-700 p-4 mb-4">
                <h2 class="font-semibold text-lg mb-3">{{ $target['extension'] }}</h2>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <h3>{{ __('provisioning.admin.domain_tlds.tools.before') }}</h3>
                        <pre class="whitespace-pre-wrap break-all">{{ json_encode($target['previous'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                    <div>
                        <h3>{{ __('provisioning.admin.domain_tlds.tools.after') }}</h3>
                        <pre class="whitespace-pre-wrap break-all">{{ json_encode($target['settings'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
                @if($operation->kind === 'copy' && $target['prices'])
                <div class="grid md:grid-cols-2 gap-4 text-sm mt-3">
                    <pre class="whitespace-pre-wrap">{{ json_encode($target['previous_prices'], JSON_PRETTY_PRINT) }}</pre>
                    <pre class="whitespace-pre-wrap">{{ json_encode($target['prices'], JSON_PRETTY_PRINT) }}</pre>
                </div>
                @endif
                @foreach($target['price_rows'] ?? [] as $row)
                <div class="grid sm:grid-cols-3 gap-3 items-center mt-4 text-sm">
                    <span>{{ __('provisioning.domain_manager.'.$row['action']) }} - {{ __('recurring.'.$row['billing']) }}<br>{{ __('provisioning.admin.domain_tlds.tools.cost') }} : {{ $row['cost'] }} {{ $row['currency'] }} × {{ $row['rate'] }}</span>
                    <span>{{ __('provisioning.admin.domain_tlds.tools.before') }} : {{ $target['previous_prices'][$operation->payload['currency']][$row['action']][$row['billing']]['price'] ?? '-' }}</span>
                    <label>{{ __('provisioning.admin.domain_tlds.tools.selling') }} ({{ $operation->payload['currency'] }})<input class="input-text" type="number" min="0" max="99999999" step="0.01" name="prices[{{ $row['key'] }}]" value="{{ old('prices.'.$row['key'], $row['selling']) }}" required></label>
                </div>
                @endforeach
            </section>
            @endforeach
            <button class="btn btn-primary">{{ __('provisioning.admin.domain_tlds.tools.confirm') }}</button>
        </form>
        @endif
    </div>
</div>
<script src="{{ Vite::asset('resources/global/js/admin/domain-tlds.js') }}" type="module"></script>
@endsection
