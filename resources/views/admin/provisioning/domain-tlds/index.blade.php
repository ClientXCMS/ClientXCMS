@extends('admin/settings/sidebar')
@section('title', __($translatePrefix .'.title'))
@section('setting')
<div class="container mx-auto">
    @include('admin.shared.alerts')
    @include('admin.provisioning.domain-tlds.partials.tools')
    <div class="card">
        <div class="card-heading">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __($translatePrefix . '.title') }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __($translatePrefix . '.subheading') }}</p>
            </div>
            <a class="btn btn-primary" href="{{ route($routePath . '.create') }}">{{ __('admin.create') }}</a>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @forelse($items as $item)
            @php
            $currencyPrices = $item->prices->where('currency', $defaultCurrency);
            $nameserverCount = count($item->default_nameservers ?? []);
            @endphp
            <article class="flex min-w-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-slate-800/60">
                    <div class="min-w-0">
                        <h3 class="truncate text-lg font-semibold text-gray-900 dark:text-white">{{ $item->extension }}
                            <x-badge-state state="{{ $item->status }}"></x-badge-state>

                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">#{{ $item->id }} · {{ $item->server?->name ?? __($translatePrefix . '.server') }}</p>
                    </div>
                    <div class="grid grid-cols-2">

                            <a href="{{ route($routePath . '.show', ['domain_tld' => $item]) }}">
                                <span class="px-1 py-1.5">
                                    <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                        <i class="bi bi-eye-fill"></i>
                                        {{ __('global.show') }}
                                    </span>
                                </span>
                            </a>
                            <form method="POST" action="{{ route($routePath . '.show', ['domain_tld' => $item]) }}" class="confirmation-popup">
                                @csrf
                                @method('DELETE')
                                <button>
                                    <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                        <i class="bi bi-trash"></i>
                                        {{ __('global.delete') }}
                                    </span>
                                </button>
                            </form>
                    </div>
                </div>
                <div class="flex-1 space-y-3 px-4 py-4">
                    <div class="flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <span class="rounded-md bg-gray-100 px-2 py-1 dark:bg-slate-800">{{ __('provisioning.admin.domain_tlds.tools.nameservers') }} : {{ $nameserverCount }}</span>
                        <span class="rounded-md bg-gray-100 px-2 py-1 dark:bg-slate-800">{{ __('provisioning.domain_manager.dns') }} : {{ $item->dns_management ? '✓' : '—' }}</span>
                    </div>
                    <dl class="space-y-1.5 border-t border-gray-100 pt-3 text-sm dark:border-gray-700">
                        @foreach(['register', 'renew', 'transfer'] as $action)
                        @php
                        $price = $currencyPrices->where('action', $action)->sortBy('price')->first();
                        @endphp
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('provisioning.domain_manager.'.$action) }}</dt>
                            <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $price ? formatted_price($price->price, $price->currency) : '—' }}@if($price)<span class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-400">{{ __('recurring.'.$price->billing) }}</span>@endif</dd>
                        </div>
                        @endforeach
                    </dl>
                </div>
            </article>
            @empty
            <div class="rounded-xl border border-gray-200 px-6 py-10 text-center text-gray-500 dark:border-gray-700 dark:text-gray-400 md:col-span-2 xl:col-span-3 2xl:col-span-4">{{ __('global.no_results') }}</div>
            @endforelse
        </div>
        <div class="py-1 px-4 mx-auto">{{ $items->links('admin.shared.layouts.pagination') }}</div>
    </div>
</div>
<script src="{{ Vite::asset('resources/global/js/admin/domain-tlds.js') }}" type="module"></script>
@endsection