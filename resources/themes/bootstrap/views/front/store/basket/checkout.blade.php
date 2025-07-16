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
@section('title', __('store.checkout.title'))
@section('scripts')
    <script src="{{ Vite::asset('resources/themes/bootstrap/js/checkout.js') }}" type="module" defer></script>
@endsection
@section('content')

    <main class="container px-4 py-4 mx-auto">
                <h3 class="mb-3">{{ __('store.checkout.title') }}</h3>
        @include("shared.alerts")
        <div class="row">
                    <div class="col-12 col-md-9">
                        <div class="card card-body" id="checkout-form">
                            @if (Auth::check())
                                <div class="d-flex justify-between mb-2 text-gray-400">
                                    <span>{{ __('auth.signed_in_as') }}</span>
                                    <span>{{ Auth::user()->FullName }} ({{ Auth::user()->email }})</span>
                                </div>
                                @if (Auth::check() && !Auth::user()->hasVerifiedEmail() && setting('checkout.customermustbeconfirmed', false))
                                    <div class="alert alert-warning d-flex align-items-center mt-2" role="alert">
                                        <p>
                                            {{ __('store.checkout.email_must_be_verified') }}
                                        </p>
                                    </div>
                                @endif
                            @else
                                <div class="container mt-4">
                                    <button type="button" class="btn btn-primary mb-2" data-bs-toggle="collapse" data-bs-target="#login-collapse-heading" aria-expanded="false" aria-controls="login-collapse-heading">
                                        {{ __('auth.login.btn') }}
                                    </button>
                                    <button type="button" class="btn btn-secondary mb-2" data-bs-toggle="collapse" data-bs-target="#register-collapse-heading" aria-expanded="false" aria-controls="register-collapse-heading">
                                        {{ __('auth.register.btn') }}
                                    </button>

                                    <div id="login-collapse-heading" class="collapse">
                                        <div class="mt-3">

                                            @if ($providers->isNotEmpty())
                                                <div class="row mt-2">

                                                    @foreach ($providers as $provider)
                                                        <div class="col-12 col-md-6">
                                                            <a href="{{ route('socialauth.authorize', $provider->name) }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center">
                                                                <img src="{{ $provider->provider()->logo() }}" alt="{{ $provider->provider()->title() }}" class="me-2" style="width: 20px; height: 20px;">
                                                                {{ __('socialauth::messages.login_with', ['provider' => $provider->provider()->title()]) }}
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <form method="POST" action="{{ route('login') }}">
                                                @include('shared.auth.login', ['redirect' => route('front.store.basket.checkout') .'#login', 'captcha' => true])
                                            </form>
                                        </div>
                                    </div>

                                    <div id="register-collapse-heading" class="collapse">
                                        <div class="mt-3">
                                            @if ($providers->isNotEmpty())
                                                <div class="row g-3">
                                                    @foreach ($providers as $provider)
                                                        <div class="col-12 col-md-6">
                                                            <a href="{{ route('socialauth.authorize', $provider->name) }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center">
                                                                <img src="{{ $provider->provider()->logo() }}" alt="{{ $provider->provider()->title() }}" class="me-2" style="width: 20px; height: 20px;">
                                                                {{ __('socialauth::messages.register_with', ['provider' => $provider->provider()->title()]) }}
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <div class="d-flex align-items-center my-3">
                                                    <hr class="flex-grow-1 me-2">
                                                    <span class="text-muted text-uppercase small">{{ trans("global.or") }}</span>
                                                    <hr class="flex-grow-1 ms-2">
                                                </div>
                                            @endif

                                            @include('shared.auth.register', ['countries' => $countries, 'redirect' => route('front.store.basket.checkout') . '#register'])
                                        </div>
                                    </div>

                                    @if (Auth::check())
                                        <form method="POST" action="{{ route('front.store.basket.checkout') }}" id="checkoutForm" class="mt-4">
                                            @csrf
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    @include("shared.input", ["name" => "firstname", "label" => __('global.firstname'), "value" => auth('web')->user()->firstname ?? old("firstname")])
                                                </div>
                                                <div class="col-md-6">
                                                    @include("shared.input", ["name" => "lastname", "label" => __('global.lastname'), "value" => auth('web')->user()->lastname ?? old("lastname")])
                                                </div>
                                                <div class="col-md-6">
                                                    @include("shared.input", ["name" => "address", "label" => __('global.address'), "value" => auth('web')->user()->address ?? old("address")])
                                                </div>
                                                <div class="col-md-6">
                                                    @include("shared.input", ["name" => "address2", "label" => __('global.address2'), "value" => auth('web')->user()->address2 ?? old("address2")])
                                                </div>
                                                <div class="col-md-4">
                                                    @include("shared.input", ["name" => "zipcode", "label" => __('global.zip'), "value" => auth('web')->user()->zipcode ?? old("zipcode")])
                                                </div>
                                                <div class="col-md-4">
                                                    @include("shared.select", ["name" => "country", "label" => __('global.country'), "options" => $countries,"value" => auth('web')->user()->country ?? old("country")])
                                                </div>
                                                <div class="col-md-4">
                                                    @include("shared.input", ["name" => "city", "label" => __('global.city'), "value" => auth('web')->user()->city ?? old("city")])
                                                </div>
                                                <div class="col-md-6">
                                                    @include("shared.input", ["name" => "email", "label" => __('global.email'), "type" => "email", "value" => auth('web')->user()->email ?? old("email"), "disabled"=> true])
                                                </div>
                                                <div class="col-md-6">
                                                    @include("shared.input", ["name" => "phone", "label" => __('global.phone'), "value" => auth('web')->user()->phone ?? old("phone")])
                                                </div>
                                            </div>

                                            @if (setting('checkout.toslink'))
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" id="accept_tos" name="accept_tos">
                                                    <label class="form-check-label" for="accept_tos">
                                                        {{ __('auth.register.accept') }} <a href="{{ setting('checkout.toslink') }}" class="link-primary">{{ __('store.checkout.terms') }}</a>
                                                    </label>
                                                </div>
                                            @endif
                                            @include('shared/captcha', ['center' => false])
                                        @if ($basket->total() != 0)
                                                <h2 class="h5 mt-4">{{ __('store.checkout.choose_payment') }}</h2>
                                                <div class="row g-3 mt-2">
                                                    @foreach ($gateways as $gateway)
                                                        <div class="col-md-4">
                                                            <label class="form-check-label w-100">
                                                                <input type="radio" name="gateway" value="{{ $gateway->uuid }}" class="form-check-input" id="gateway-{{ $gateway->uuid }}" {{ $loop->last ? 'checked' : '' }}>
                                                                <div class="card text-center">
                                                                    <img src="{{ $gateway->paymentType()->image() }}" class="card-img-top p-2" alt="{{ $gateway->name }}">
                                                                    <div class="card-body">
                                                                        <h5 class="card-title">{{ $gateway->getGatewayName() }}</h5>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
            <div class="col-12 col-md-3">
                <div class="card">
                    <div class="d-flex justify-content-between align-items-center p-3">
                        <h2 class="h5 mb-0">{{ __('store.config.summary') }}</h2>
                        <button type="button" class="btn btn-link p-0" data-bs-toggle="collapse" data-bs-target="#hs-checkout-collapse" aria-expanded="false" aria-controls="hs-checkout-collapse">
                            <svg class="rotate-180" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="card-body">
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
                            <div class="d-flex justify-content-between mb-2 collapse">
                                <span>{{ __('coupon.coupon') }}</span>
                                <span id="coupon" class="text-primary">{{ $basket->coupon->code }}</span>
                            </div>
                        @endif

                        <div id="hs-checkout-collapse" class="collapse">
                            <hr class="my-2">

                            @if ($basket->coupon)
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

                                <div class="d-flex justify-content-between mb-2">
                                    <span>{{ __('store.config.recurring_payment') }}</span>
                                    <span id="recurring">{{ formatted_price($basket->recurringPayment(), $basket->currency()) }}</span>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span>{{ __('store.fees') }}</span>
                                    <span id="fees">{{ formatted_price($basket->setup(), $basket->currency()) }}</span>
                                </div>

                                @if ($basket->coupon->free_setup == 1 && $basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES) > 0)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ __('coupon.free_setup') }}</span>
                                        <span id="free_setup" class="text-primary">-{{ formatted_price($basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES), $basket->currency()) }}</span>
                                    </div>
                                @endif

                                <div class="d-flex justify-content-between mb-2">
                                    <span>{{ $basket->coupon ? __('coupon.subtotal_without_coupon') : __('store.subtotal') }}</span>
                                    <span id="subtotal">{{ formatted_price($basket->subtotalWithoutCoupon(), $basket->currency()) }}</span>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span>{{ __('store.vat') }}</span>
                                    <span id="taxes">{{ formatted_price($basket->tax(), $basket->currency()) }}</span>
                                </div>
                            @endif
                        </div>

                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('store.config.recurring_payment') }}</span>
                            <span id="recurring">{{ formatted_price($basket->recurringPayment(), $basket->currency()) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('store.vat') }}</span>
                            <span id="taxes">{{ formatted_price($basket->tax(), $basket->currency()) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('store.fees') }}</span>
                            <span id="fees">{{ formatted_price($basket->setup(), $basket->currency()) }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-semibold">{{ __('store.basket.paytoday') }}</span>
                            <span class="fw-semibold" id="total">{{ formatted_price($basket->total(), $basket->currency()) }}</span>
                        </div>
                            <button type="submit" @guest disabled @endguest class="btn btn-primary w-100 mt-4" id="btnCheckout">Checkout</button>

                    </div>

                </div>
            </div>

        </div>
    </main>


@endsection
