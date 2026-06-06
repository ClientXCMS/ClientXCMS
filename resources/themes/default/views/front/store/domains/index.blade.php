@extends('layouts/front')
@section('title', __('provisioning.domain_manager.search.title'))
@section('content')
    <div class="{{ theme_metadata('layout_classes', 'max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto') }}">
        @include('shared.alerts')
        <h1 class="text-2xl font-semibold mb-4 dark:text-white">{{ __('provisioning.domain_manager.search.title') }}</h1>
        <form method="POST" action="{{ route('front.store.domains.search') }}" class="card">
            @csrf
            <div class="grid md:grid-cols-4 gap-3">
                <div class="md:col-span-3">
                    @include('shared/input', ['name' => 'domain', 'label' => __('provisioning.domain_manager.domain'), 'value' => old('domain', $query), 'placeholder' => 'example'])
                </div>
                <button class="btn-primary mt-2">{{ __('provisioning.domain_manager.search.submit') }}</button>
            </div>
        </form>
        @if($product === null)
            <div class="card mt-4 text-amber-600">{{ __('provisioning.domain_manager.search.no_product') }}</div>
        @endif
        @if($results->isNotEmpty())
            <div class="card mt-4">
                @foreach($results as $result)
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 py-3 border-b last:border-0 dark:border-gray-700">
                        <div>
                            <p class="font-semibold dark:text-white">{{ $result['domain'] }}</p>
                            <p class="text-sm {{ $result['availability']->available ? 'text-green-600' : 'text-red-600' }}">
                                {{ $result['availability']->available ? __('provisioning.domain_manager.search.available') : __('provisioning.domain_manager.search.unavailable') }}
                            </p>
                        </div>
                        @if($result['availability']->available && $product && !empty($result['prices']))
                            @php($price = $result['prices'][0])
                            <a class="btn-primary" href="{{ route('front.store.basket.config', ['product' => $product, 'domain' => $result['domain'], 'tld' => $result['tld']->extension, 'billing' => $price->recurring]) }}">
                                {{ formatted_price($price->firstPayment(), $price->currency) }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
