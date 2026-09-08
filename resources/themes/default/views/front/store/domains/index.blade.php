@extends('layouts/front')
@section('title', __('provisioning.domain_manager.search.title'))
@section('content')
    <div class="{{ theme_metadata('layout_classes', 'max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto') }}">
        @include('shared.alerts')
        <h1 class="text-3xl font-bold mb-6 text-slate-900 dark:text-white">{{ __('provisioning.domain_manager.search.title') }}</h1>
        <form method="POST" action="{{ route('front.store.domains.search') }}" class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
            @csrf
            <div class="grid md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-3">
                    @include('shared/input', ['name' => 'domain', 'label' => __('provisioning.domain_manager.domain'), 'value' => old('domain', $query), 'placeholder' => 'example'])
                </div>
                <button class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-colors mt-2">{{ __('provisioning.domain_manager.search.submit') }}</button>
            </div>
        </form>
        @if($product === null)
            <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 p-5 mt-6 text-amber-600">{{ __('provisioning.domain_manager.search.no_product') }}</div>
        @endif
        @if($results->isNotEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800 text-slate-700 dark:text-slate-300 mt-6">
                @foreach($results as $result)
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 py-5 border-b border-slate-100 last:border-0 dark:border-slate-700">
                        <div>
                            <p class="text-lg font-semibold break-words dark:text-white">{{ $result['domain'] }}</p>
                            <p class="text-sm {{ $result['availability']->available ? 'text-green-600' : 'text-red-600' }}">
                                {{ $result['availability']->available ? __('provisioning.domain_manager.search.available') : __('provisioning.domain_manager.search.unavailable') }}
                            </p>
                        </div>
                        @if($result['availability']->available && $product && !empty($result['prices']))
                            @php($price = $result['prices'][0])
                            <a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-colors" href="{{ route('front.store.basket.config', ['product' => $product, 'domain' => $result['domain'], 'tld' => $result['tld']->extension, 'billing' => $price->recurring]) }}">
                                {{ formatted_price($price->firstPayment(), $price->currency) }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
