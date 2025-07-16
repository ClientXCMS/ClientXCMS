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
@section('title', __('store.basket.title'))
@section('scripts')
    <script src="{{ Vite::asset('resources/themes/default/js/basket.js') }}" type="module"></script>
@endsection
@section('content')

    <div class="container px-4 py-4 mx-auto">
        @include("shared.alerts")

        <h2 class="mb-2">{{ __('store.basket.title') }}</h2>
        <div class="row">
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="container my-4">
                        <div class="table-responsive">
                            <table class="table align-middle bg-white">
                                <thead>
                                <tr>
                                    <th class="text-start fw-semibold">{{ __('global.product') }}</th>
                                    <th class="text-start fw-semibold">{{ __('store.price') }}</th>
                                    <th class="text-start fw-semibold">{{ __('store.qty') }}</th>
                                    <th class="text-start fw-semibold">{{ __('store.total') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if ($basket->items()->count() == 0)
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <div class="d-flex flex-column justify-content-center align-items-center p-4">
                                                <i class="bi bi-cart fs-3"></i>
                                                <p class="mt-1 text-muted">
                                                    {{ __('store.basket.empty') }}
                                                </p>
                                                <a href="{{ route('front.store.index') }}" class="btn btn-outline-primary mt-1">
                                                    {{ __('store.basket.continue') }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @foreach($basket->items()->get() as $row)
                                    @php($pricing = $row->product->getPriceByCurrency($row->currency, $row->billing))
                                    <tr>
                                        <td class="py-3">
                                            <div class="d-flex align-items-center">
                                                <form method="POST" action="{{ route('front.store.basket.remove', ['product' => $row->product]) }}" class="me-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="{{ __('store.basket.remove') }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                <a href="{{ $row->product->data_url() }}" title="{{ __('store.config.title') }}" class="btn btn-sm btn-outline-secondary me-2">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <span class="fw-semibold">{{ $row->product->trans('name') }}</span>
                                                @if ($row->primary() != null)
                                                    <span class="badge bg-primary ms-2">{{ $row->primary() }}</span>
                                                @endif
                                            </div>
                                            @if (!empty($row->optionsFormattedName()))
                                                <small class="text-muted d-block mt-2">
                                                    @foreach ($row->optionsFormattedName(false) as $name)
                                                        {{ $name }}<br>
                                                    @endforeach
                                                </small>
                                            @endif
                                        </td>
                                        <td class="py-3">{{ formatted_price($pricing->firstPayment(), $pricing->currency) }}</td>
                                        <td class="py-3">
                                            @if ($row->canChangeQuantity())
                                                <form action="{{ route('front.store.basket.quantity', ['product' => $row->product]) }}" method="POST" class="d-flex align-items-center">
                                                    @csrf
                                                    <button class="btn btn-outline-secondary btn-sm me-2" name="minus">-</button>
                                                    <span class="text-center">{{ $row->quantity }}</span>
                                                    <button class="btn btn-outline-secondary btn-sm ms-2" name="plus">+</button>
                                                </form>
                                            @else
                                                <span class="text-center">{{ $row->quantity }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3">{{ formatted_price($row->total(), $row->currency) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-body">
                <h4 class="mb-4">{{ __('coupon.add_coupon_title') }}</h4>
                <form method="POST" action="{{ route('front.store.basket.coupon') }}">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-md-9">
                            @include('shared/input', ['name' => 'coupon', 'attributes' => ['placeholder' => __('coupon.coupon_placeholder')], 'value' => old('coupon', $basket->coupon ? $basket->coupon->code : null)])
                        </div>
                        @if ($basket->coupon)
                            @method('DELETE')
                        @endif
                        <div class="col-12 col-md-3">
                            @if ($basket->coupon)
                                <button type="submit" class="btn btn-outline-danger w-100 h-100"><i class="bi bi-x-circle mr-3"></i> <span class="md:inline-block hidden">{{ __('coupon.remove_coupon') }}</span></button>
                            @else
                                <button type="submit" class="btn btn-outline-primary w-100 h-100"><i class="bi bi-ticket-perforated mr-3"></i>
                                    <span class="md:inline-block hidden">{{ __('coupon.add_coupon') }}</span></button>
                            @endif
                        </div>

                    </div>
                </form>
                </div>
            </div>
        </div>

            <div class="col-12 col-md-4">
                <div class="card text-muted">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="h5">{{ __('store.config.summary') }}</h2>
                            <button
                                type="button"
                                class="btn btn-outline-primary btn-sm"
                                data-bs-toggle="collapse"
                                data-bs-target="#checkout-collapse"
                                aria-expanded="false"
                                aria-controls="checkout-collapse">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>

                        @foreach($basket->items as $row)
                            @php($pricing = $row->product->getPriceByCurrency($row->currency, $row->billing))

                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ $row->product->trans('name') }}</span>
                                <span>{{ formatted_price($row->subtotalWithoutCoupon(), $row->currency) }}</span>
                            </div>

                            @if (!empty($row->getOptions()))
                                <hr class="my-2">
                                @foreach ($row->getOptions() as $option)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ $option->formattedName() }}</span>
                                        <span>{{ formatted_price($option->subtotal($row->currency, $row->billing), $row->currency) }}</span>
                                    </div>
                                @endforeach
                            @endif
                        @endforeach

                        @if ($basket->coupon)
                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ __('coupon.coupon') }}</span>
                                <span id="coupon" class="text-primary">{{ $basket->coupon->code }}</span>
                            </div>
                        @endif

                        <div class="collapse" id="checkout-collapse">
                            @if ($basket->coupon)
                                <hr class="my-2">
                                @if ($basket->discount(\App\Models\Store\Basket\BasketRow::PRICE))
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ __('coupon.discount_price') }}</span>
                                        <span id="discount" class="text-primary">-{{ formatted_price($basket->discount(\App\Models\Store\Basket\BasketRow::PRICE), $basket->currency()) }}</span>
                                    </div>
                                @endif
                                @if ($basket->coupon->free_setup == 0 && $basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES) > 0)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ __('coupon.discount_setup') }}</span>
                                        <span id="discount" class="text-primary">-{{ formatted_price($basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES), $basket->currency()) }}</span>
                                    </div>
                                @endif
                                @if ($basket->coupon->free_setup == 1 && $basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES) > 0)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ __('coupon.free_setup') }}</span>
                                        <span id="free_setup" class="text-primary">-{{ formatted_price($basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES), $basket->currency()) }}</span>
                                    </div>
                                @endif
                            @endif
                            <hr class="my-2">
                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ __('coupon.subtotal_with_coupon') }}</span>
                                <span id="subtotal">{{ formatted_price($basket->subtotal(), $basket->currency()) }}</span>
                            </div>
                        </div>

                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('store.config.recurring_payment') }}</span>
                            <span id="recurring">{{ formatted_price($basket->recurringPayment(), $basket->currency()) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('store.config.onetime_payment') }}</span>
                            <span id="onetime">{{ formatted_price($basket->onetimePayment(), $basket->currency()) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('store.fees') }}</span>
                            <span id="fees">{{ formatted_price($basket->setup(), $basket->currency()) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('store.vat') }}</span>
                            <span id="taxes">{{ formatted_price($basket->tax(), $basket->currency()) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ $basket->coupon ? __('coupon.subtotal_without_coupon') : __('store.subtotal') }}</span>
                            <span id="subtotal">{{ formatted_price($basket->subtotalWithoutCoupon(), $basket->currency()) }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-semibold">{{ __('store.basket.paytoday') }}</span>
                            <span class="fw-semibold" id="total">{{ formatted_price($basket->total(), $basket->currency()) }}</span>
                        </div>
                    <a href="{{ route('front.store.basket.checkout') }}" class="btn btn-primary w-100 mt-4 text-center">{{ __('store.basket.finish') }}</a>
                    </div>
                </div>
            </div>
        </div>

    {!! render_theme_sections() !!}


@endsection
