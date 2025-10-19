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
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */
?>

@extends('layouts/front')
@section('title', __('store.checkout.title'))
@section('scripts')
    <script src="{{ Vite::asset('resources/themes/default/js/checkout.js') }}" type="module" defer></script>
@endsection
@section('content')

    <main class="{{ theme_metadata('layout_classes', 'max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto') }}">
        @if (theme_metadata('enable_pagetitle', 'false') == 'false')
                <h1 class="text-2xl font-semibold mb-4 dark:text-white">{{ __('store.checkout.title') }}</h1>
        @endif
        @include("shared.alerts")
        <div class="flex flex-col md:flex-row gap-4">
                    <div class="md:w-3/4">
                        <div class="card card-body" id="checkout-form">
                            @if (Auth::check())
                                <div class="flex justify-between mb-2 text-gray-400">
                                    <span>{{ __('auth.signed_in_as') }}</span>
                                    <span>{{ Auth::user()->FullName }} ({{ Auth::user()->email }})</span>
                                </div>
                                @if (Auth::check() && !Auth::user()->hasVerifiedEmail() && setting('checkout.customermustbeconfirmed', false))
                                    <div class="bg-gray-50 border border-gray-200 text-sm text-gray-600 rounded-lg p-4 dark:bg-white/10 dark:border-white/10 dark:text-neutral-400" role="alert" tabindex="-1" aria-labelledby="hs-link-on-right-label">
                                        <div class="flex">
                                            <div class="shrink-0">
                                                <svg class="shrink-0 size-4 mt-0.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <path d="M12 16v-4"></path>
                                                    <path d="M12 8h.01"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1 md:flex md:justify-between ms-2 my-auto">
                                                <p id="hs-link-on-right-label" class="text-sm">
                                                    {{ __('store.checkout.email_must_be_verified') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @else
                            <button type="button" class="block hs-collapse-toggle py-3 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" id="login-collapse-collapse" data-hs-collapse="#login-collapse-heading">
                                {{ __('auth.login.btn') }}
                            </button>
                            <button type="button" class="block hs-collapse-toggle py-3 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-indigo-100 text-indigo-800 hover:bg-indigo-200 disabled:opacity-50 disabled:pointer-events-none dark:hover:bg-indigo-900 dark:text-indigo-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" id="register-collapse-collapse" data-hs-collapse="#register-collapse-heading">
                                {{ __('auth.register.btn') }}
                            </button>
                            <div id="login-collapse-heading" class="hs-collapse hidden w-full overflow-hidden transition-[height] duration-300" aria-labelledby="login-collapse">
                                <div class="mt-5">

                                @if ($providers->isNotEmpty())
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                        @foreach ($providers as $provider)
                                            <a href="{{ route('socialauth.authorize', $provider->name) }}" class="mt-2 w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">

                                                <img src="{{ $provider->provider()->logo() }}" alt="{{ $provider->provider()->title() }}" class="w-5 h-5" />
                                                {{ __('socialauth::messages.login_with', ['provider' => $provider->provider()->title()]) }}
                                            </a>
                                        @endforeach
                                    </div>

                                    <div class="py-3 flex items-center text-xs text-gray-400 uppercase before:flex-[1_1_0%] before:border-t before:border-gray-200 before:me-6 after:flex-[1_1_0%] after:border-t after:border-gray-200 after:ms-6 dark:text-gray-500 dark:before:border-gray-600 dark:after:border-gray-600">
                                        {{ trans("global.or") }}</div>
                                @endif
                                    <p class="text-gray-500 dark:text-gray-400">
                                    <form method="POST" action="{{ route('login') }}">
                                    @include('shared.auth.login', ['redirect' => route('front.store.basket.checkout') .'#login', 'captcha' => true])
                                    </form>
                                    </p>
                                </div>
                            </div>

                            <div id="register-collapse-heading" class="hs-collapse hidden w-full overflow-hidden transition-[height] duration-300" aria-labelledby="register-collapse">
                                <div class="mt-5">

                                @if ($providers->isNotEmpty())
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                        @foreach ($providers as $provider)
                                            <a href="{{ route('socialauth.authorize', $provider->name) }}" class="mt-2 w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">

                                                <img src="{{ $provider->provider()->logo() }}" alt="{{ $provider->provider()->title() }}" class="w-5 h-5" />
                                                {{ __('socialauth::messages.register_with', ['provider' => $provider->provider()->title()]) }}
                                            </a>
                                        @endforeach
                                    </div>

                                    <div class="py-3 flex items-center text-xs text-gray-400 uppercase before:flex-[1_1_0%] before:border-t before:border-gray-200 before:me-6 after:flex-[1_1_0%] after:border-t after:border-gray-200 after:ms-6 dark:text-gray-500 dark:before:border-gray-600 dark:after:border-gray-600">
                                        {{ trans("global.or") }}</div>
                                @endif
                                    <p class="text-gray-500 dark:text-gray-400">
                                        @include('shared.auth.register', ['countries' => $countries, 'redirect' => route('front.store.basket.checkout') . '#register'])
                                    </p>
                                </div>
                            </div>
                            @endif
                                @if (Auth::check())
                                    <form method="POST" action="{{ route('front.store.basket.checkout') }}" id="checkoutForm">
                                        @csrf

                                <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6">
                                    <div class="sm:col-span-2">
                                        @include("shared.input", ["name" => "firstname", "label" => __('global.firstname'), "value" => auth('web')->user()->firstname ?? old("firstname")])
                                    </div>


                                    <div class="sm:col-span-2">
                                        @include("shared.input", ["name" => "lastname", "label" => __('global.lastname'), "value" => auth('web')->user()->lastname ?? old("lastname")])
                                    </div>

                                    <div class="sm:col-span-2">
                                        @include("shared.input", ["name" => "company_name", "label" => __('global.company_name'), "value" => auth('web')->user()->company_name ?? old("company_name")])
                                    </div>

                                    <div class="sm:col-span-3">
                                        @include("shared.input", ["name" => "address", "label" => __('global.address'), "value" => auth('web')->user()->address ?? old("address")])
                                    </div>
                                    <div class="sm:col-span-2">
                                        @include("shared.input", ["name" => "address2", "label" => __('global.address2'), "value" => auth('web')->user()->address2 ?? old("address2")])
                                    </div>

                                    <div class="sm:col-span-1">
                                        @include("shared.input", ["name" => "zipcode", "label" => __('global.zip'), "value" => auth('web')->user()->zipcode ?? old("zipcode")])
                                    </div>

                                    <div class="sm:col-span-3">
                                        @include("shared.input", ["name" => "email", "label" => __('global.email'), "type" => "email", "value" => auth('web')->user()->email ?? old("email"), "disabled"=> true])
                                    </div>


                                    <div class="sm:col-span-3">
                                        @include("shared.input", ["name" => "phone", "label" => __('global.phone'), "value" => auth('web')->user()->phone ?? old("phone")])
                                    </div>

                                    <div class="sm:col-span-2">
                                        @include("shared.select", ["name" => "country", "label" => __('global.country'), "options" => $countries,"value" => auth('web')->user()->country ?? old("country")])
                                    </div>

                                    <div class="sm:col-span-2">
                                        @include("shared.input", ["name" => "city", "label" => __('global.city'), "value" => auth('web')->user()->city ?? old("city")])
                                    </div>

                                    <div class="sm:col-span-2">
                                        @include("shared.input", ["name" => "region", "label" => __('global.region'), "value" => auth('web')->user()->region ?? old("region")])
                                    </div>

                                    <div class="sm:col-span-6">
                                        @include("shared/textarea", ["name" => "billing_details", "label" => __('global.billing_details'), "value" => auth('web')->user()->billing_details ?? old("billing_details"), "help" => __('global.billing_details_help')])
                                    </div>
                                </div>

                                        @if (setting('checkout.toslink'))
                                            <div class="sm:col-span-3 flex gap-x-6 mb-2 mt-3">
                                                <div class="flex h-6 items-center">
                                                    <input id="accept_tos" name="accept_tos" type="checkbox" class="h-4 w-4 rounded border-gray-300 @error("accept_tos") border-red-300 @enderror text-indigo-600 focus:ring-indigo-600">
                                                </div>
                                                <div class="text-sm leading-6">
                                                    <label for="accept_tos" class="font-medium @error("accept_tos") text-red-300 dark:text-red-500 @else text-gray-900 dark:text-white @enderror">{{ __('auth.register.accept') }} <a href="{{ setting('checkout.toslink') }}" class="text-indigo-600">{{ __('store.checkout.terms') }}</a></label>
                                                </div>
                                            </div>
                                        @endif
                                        @include('shared/captcha', ['center' => false])

                                    @if ($basket->total() != 0)
                                <div class="col-span-3">
                                    <h2 class="text-lg font-semibold mb-4 mt-2 dark:text-white">{{ __('store.checkout.choose_payment') }}</h2>
                                </div>
                                        @if (auth('web')->user()->paymentMethods()->isNotEmpty())
                                            <div class="mb-3">
                                            <h3 class="font-semibold mt-2 dark:text-white">{{ __('store.checkout.choose_payment_method') }}</h3>
                                                <p class="text-gray-500 dark:text-gray-400 mb-2">{{ __('store.checkout.choose_payment_method_description') }}</p>

                                                @include('shared/select', [
                                                            'name' => 'paymentmethod',
                                                            'options' => auth('web')->user()->getPaymentMethodsArray()->merge(['none' => __('store.checkout.not_use_payment_method')]),
                                                            'value' => 'none'
                                                        ])
                                            </div>
                                            @endif
                                <div class="grid grid-cols-3 gap-4 gateway-container">

                                @foreach ($gateways as $gateway)
                                        <label for="gateway-{{ $gateway->uuid }}" class="{{ $loop->last ? 'gateway-selected' : '' }} flex flex-col group bg-white border shadow-sm rounded-xl overflow-hidden hover:shadow-lg transition dark:shadow-slate-700/[.7] dark:bg-gray-700 dark:text-white dark:text-gray-400 border-b border-gray-900/10">
                                            <input type="radio" name="gateway" value="{{ $gateway->uuid }}" {{ $loop->last ? 'checked' : '' }} class="gateway-input hidden shrink-0 ms-auto mt-0.5 border-gray-200 rounded-full text-indigo-600 focus:ring-indigo-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-indigo-500 dark:checked:border-indigo-500 dark:focus:ring-offset-gray-800" id="gateway-{{ $gateway->uuid }}">

                                            <div class="relative rounded-t-xl overflow-hidden">
                                                <img class="transition-transform mx-auto justify-duration-500 ease-in-out rounded-t-xl mt-3 mb-3" src="{{ $gateway->paymentType()->image() }}" alt="{{ $gateway->name }}" height="128" width="128">
                                            </div>
                                            <div class="p-4 md:p-5">
                                                <h3 class="text-lg text-center font-bold text-gray-800 dark:text-white">
                                                    {{ $gateway->getGatewayName() }}
                                                </h3>
                                            </div>
                                        </label>
                                @endforeach
                                </div>
                                            @endif
                                @endif
                        </form>
                        </div>
                    </div>
                    <div class="md:w-1/4">
                        <div class="card dark:text-gray-400">
                            <div class="flex justify-between">
                            <h2 class="text-lg font-semibold mb-4">{{ __('store.config.summary') }}</h2>
                            <button type="button" class="hs-collapse-toggle mb-4 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-blue-600 hover:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400" id="checkout-collapse" data-hs-collapse="#hs-checkout-collapse">
                                <svg class="hs-collapse-open:rotate-180 flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </button>
                            </div>
                            @foreach($basket->items as $row)
                                @php($pricing = $row->product->getPriceByCurrency($row->currency, $row->billing))

                                <div class="flex justify-between mb-2">
                                    <span>{{ $row->product->trans('name') }}</span>
                                    <span>{{ formatted_price($row->subtotalWithoutCoupon(), $row->currency) }}</span>
                                </div>

                                @if (!empty($row->getOptions()))
                                    <hr class="my-2">
                                    @foreach ($row->getOptions() as $option)
                                        <div class="flex justify-between mb-2">
                                            <span>{{ $option->formattedName() }}</span>
                                            <span>{{ formatted_price($option->subtotal($row->currency, $row->billing), $row->currency) }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            @endforeach
                            @if ($basket->coupon)
                                <div class="flex justify-between mb-2 hs-collapse-open:hidden">
                                    <span>{{ __('coupon.coupon') }}</span>
                                    <span id="coupon" class="text-primary">{{ $basket->coupon->code }}</span>
                                </div>
                            @endif
                            <div id="hs-checkout-collapse" class="hs-collapse w-full overflow-hidden transition-[height] duration-300" aria-labelledby="hs-show-hide-collapse">
                            <hr class="my-2">


                            @if ($basket->coupon)
                                <hr class="my-2">
                                @if ($basket->discount(\App\Models\Store\Basket\BasketRow::PRICE))
                                    <div class="flex justify-between mb-2">
                                        <span>{{ __('coupon.discount_price') }}</span>
                                        <span id="discount" class="text-primary">-{{ formatted_price($basket->discount(\App\Models\Store\Basket\BasketRow::PRICE), $basket->currency()) }}</span>
                                    </div>
                                @endif
                                @if ($basket->coupon->free_setup == 0 && $basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES) > 0)
                                    <div class="flex justify-between mb-2">
                                        <span>{{ __('coupon.discount_setup') }}</span>
                                        <span id="discount" class="text-primary">-{{ formatted_price($basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES), $basket->currency()) }}</span>
                                    </div>
                                @endif
                            <div class="flex justify-between mb-2">
                                <span>{{ __('store.config.recurring_payment') }}</span>
                                <span id="recurring">{{ formatted_price($basket->recurringPayment(), $basket->currency()) }}</span>
                            </div>

                                    <div class="flex justify-between mb-2">
                                        <span>{{ __('store.fees') }}</span>
                                        <span id="fees">{{ formatted_price($basket->setup(), $basket->currency()) }}</span>
                                    </div>
                                    @if ($basket->coupon->free_setup == 1 && $basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES) > 0)
                                        <div class="flex justify-between mb-2">
                                            <span>{{ __('coupon.free_setup') }}</span>
                                            <span id="free_setup" class="text-primary">-{{ formatted_price($basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES), $basket->currency()) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between mb-2">
                                        <span>{{ $basket->coupon ? __('coupon.subtotal_without_coupon') : __('store.subtotal') }}</span>
                                        <span id="subtotal">{{ formatted_price($basket->subtotalWithoutCoupon(), $basket->currency()) }}</span>
                                    </div>
                            <div class="flex justify-between mb-2">
                                <span>{{ __('store.vat') }}</span>
                                <span id="taxes">{{ formatted_price($basket->tax(), $basket->currency()) }}</span>
                            </div>
                            </div>
                            <hr class="my-2">

                            <div class="flex justify-between mb-2">
                                <span>{{ __('coupon.subtotal_with_coupon') }}</span>
                                <span id="subtotal">{{ formatted_price($basket->subtotal(), $basket->currency()) }}</span>
                            </div>
                            @endif

                            <div class="flex justify-between mb-2">
                                <span>{{ __('store.config.recurring_payment') }}</span>
                                <span id="recurring">{{ formatted_price($basket->recurringPayment(), $basket->currency()) }}</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span>{{ __('store.vat') }}</span>
                                <span id="taxes">{{ formatted_price($basket->tax(), $basket->currency()) }}</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span>{{ __('store.fees') }}</span>
                                <span id="fees">{{ formatted_price($basket->setup(), $basket->currency()) }}</span>
                            </div>
                            <hr class="my-2">
                            <div class="flex justify-between mb-2">
                                <span class="font-semibold">{{ __('store.basket.paytoday') }}</span>
                                <span class="font-semibold" id="total">{{ formatted_price($basket->total(), $basket->currency()) }}</span>
                            </div>
                        </div>
                        <button type="submit"  @guest disabled @endguest class="btn-primary mt-4 w-full" id="btnCheckout">Checkout</button>

                    </div>
            </div>
    </main>
    {!! render_theme_sections() !!}
@endsection
