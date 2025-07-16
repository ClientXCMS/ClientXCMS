<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
?>
?>
?>
@extends('layouts/front')
@section('title', $product->name)
@section('scripts')

    <script>
        window.optionsPrices = @json($options_prices);
        window.taxPercent = '{{ tax_percent($row->currency) }}';
        window.translations = @json(['setupfee' => __('store.setup_price'), 'recurring' => __('store.config.recurring_payment'), 'onetime' => __('store.config.onetime_payment')]);
        window.per = '{{ __('store.per') }}';
        window.currency = '{{ currency() }}';
        window.recurrings = @json(app(\App\Services\Store\RecurringService::class)->getRecurringTypes());
    </script>
    <script src="{{ Vite::asset('resources/themes/default/js/basket.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container px-4 py-4 mx-auto">
    <form method="POST" action="{{ route('front.store.basket.config', ['product' => $product]) }}">
            <input type="hidden" name="currency" value="{{ $row->currency }}" id="currency">
            @csrf
            @include("shared.alerts")
            <h3 class="mb-2">{{ __('store.config.title') }}</h3>
            <div class="row">
                <div class="col-12 col-md-9">
                    <div class="card">
                        <div class="card-body">
                    @php($pricings = $product->pricingAvailable(currency()))
                            <h3 class="mb-4">{{ __('store.config.billing') }}</h3>
                            <div class="row px-3" id="basket-billing-section">
                            @foreach ($pricings as $pricing)
                            <label for="billing-{{ $pricing->recurring }}-{{ $pricing->currency }}" class="col-12 col-md-4 pt-3 m-1 border rounded">
    <span class="fw-semibold">
        @if ($pricing->isFree())
            {{ __('global.free') }}
        @else
            {{ $pricing->getPriceByDisplayMode() }} {{ $pricing->getSymbol() }}
        @endif
        {{ $pricing->recurring()['translate'] }}
    </span>
        <p class="mb-0 text-muted">
            {{ $pricing->pricingMessage() }}
            @if ($pricing->hasDiscountOnRecurring($product->getFirstPrice()))
                <span class="badge bg-success text-light">
                    -{{ $pricing->getDiscountOnRecurring($product->getFirstPrice()) }}%
                </span>
            @endif
        </p>
                                <input
                                    type="radio"
                                    name="billing"
                                    value="{{ $pricing->recurring }}"
                                    {{ $billing == $pricing->recurring ? 'checked' : '' }}
                                    data-pricing="{{ $pricing->toJson() }}"
                                    class="form-check-input mb-3 float-end border-primary rounded-circle text-primary focus:ring-primary"
                                    id="billing-{{ $pricing->recurring }}-{{ $pricing->currency }}"
                                >
                            </label>

                        @endforeach
                    </div>
                    @if (!empty($options_html))
                        <div class="pt-2">
                            <h3>{{ __('store.config.options') }}</h3>
                            {!! $options_html !!}
                        </div>
                    @endif
                    @if (!empty($data_html))
                        <div class="pt-6">

                        {!! $data_html !!}
                        </div>
                        @endif

                </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="fw-semibold mb-4">{{ __('store.config.summary') }}</h3>
                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ __('global.product') }}</span>
                                <span>{{ $row->product->name }}</span>
                            </div>
                            @if ($options->isNotEmpty())
                                <hr class="my-2">
                                @foreach ($options as $option)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span id="options_name[{{ $option->key }}]" data-name="{{ $option->name }}">{{ $option->name }}</span>
                                        <span id="options_price[{{ $option->key }}]">0</span>
                                    </div>
                                @endforeach
                                <hr class="my-2">
                            @endif
                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ __('store.config.recurring_payment') }}</span>
                                <span id="recurring">0</span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ __('store.config.onetime_payment') }}</span>
                                <span id="onetime">0</span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ __('store.fees') }}</span>
                                <span id="fees">0</span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ __('store.subtotal') }}</span>
                                <span id="subtotal">0</span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ __('store.vat') }}</span>
                                <span id="taxes">0</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-semibold">{{ __('store.total') }}</span>
                                <span class="fw-semibold" id="total">0</span>
                            </div>
                            <button class="btn btn-primary mt-4 w-100">{{ __('store.basket.addtocart') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>


@endsection
