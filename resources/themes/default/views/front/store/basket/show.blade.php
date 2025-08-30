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
@section('title', __('store.basket.title'))
@section('scripts')
    <script src="{{ Vite::asset('resources/themes/default/js/basket.js') }}" type="module"></script>
@endsection
@section('content')

    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        @include("shared.alerts")
        @if (theme_metadata('enable_pagetitle', 'false') == 'false')

        <h1 class="text-2xl font-semibold mb-4 dark:text-white">{{ __('store.basket.title') }}</h1>
        @endif
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="md:w-3/4">
                        <div class="card">
                            <div class="flex flex-col">
                                <div class="-m-1.5 overflow-x-auto">
                                    <div class="p-1.5 min-w-full inline-block align-middle">
                                        <div class="overflow-hidden">
                            <table class="w-full">
                                <thead>
                                <tr>
                                    <th class="text-left font-semibold dark:text-gray-400">{{ __('global.product') }}</th>
                                    <th class="text-left font-semibold dark:text-gray-400">{{ __('store.price') }}</th>
                                    <th class="text-left font-semibold dark:text-gray-400">{{ __('store.qty') }}</th>
                                    <th class="text-left font-semibold dark:text-gray-400">{{ __('store.total') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if ($basket->items()->count() == 0)
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                                                @include("shared.icons.shopping-cart")
                                                <p class="mt-5 text-sm text-gray-800 dark:text-gray-400">
                                                    {{ __('store.basket.empty') }}
                                                </p>
                                                <a href="{{ route('front.store.index') }}" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-indigo-600 hover:text-indigo-800 disabled:opacity-50 disabled:pointer-events-none dark:text-indigo-500 dark:hover:text-indigo-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">{{ __('store.basket.continue') }}</a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                @foreach($basket->items()->get() as $row)
                                    @php($pricing = $row->product->getPriceByCurrency($row->currency, $row->billing))
                                    <tr class="dark:text-gray-500">
                                    <td class="py-4 ">
                                        <div class="flex items-center">
                                            <form method="POST" action="{{ route('front.store.basket.remove', ['product' => $row->product]) }}">
                                                @csrf
                                                @method('DELETE')
                                            <button type="submit" title="{{ __('store.basket.remove') }}" class="btn-icon dark:bg-red-500 dark:text-white border-red-200 text-red-800 dark:hover:bg-red-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                            </button>
                                            </form>
                                            <a href="{{ $row->product->data_url() }}" title="{{ __('store.config.title') }} " class="btn-icon border-gray-200 text-gray-800 dark:text-gray-400 dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                                @include('shared/icons/edit')
                                            </a>

                                            <span class="font-semibold">{{ $row->product->trans('name') }}</span>
                                            @if ($row->primary() != null)
                                                <span class="text-primary ml-2">{{ $row->primary() }}</span>
                                                @endif
                                        </div>

                                        @if (!empty($row->optionsFormattedName()))
                                            <span class="text-gray-500 mt-2">
                                                @foreach ($row->optionsFormattedName(false) as $name)
                                                    {{ $name }}
                                                    <br/>
                                                @endforeach
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4">{{ formatted_price($pricing->firstPayment(),$pricing->currency)  }}</td>
                                    <td class="py-4">
                                        @if ($row->canChangeQuantity())
                                            <form class="flex items-center" action="{{ route('front.store.basket.quantity', ['product' => $row->product]) }}" method="POST">
                                                @csrf
                                                <button class="border rounded-md py-2 px-4 mr-2 btn-icon dark:text-gray-400 dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" name="minus">-</button>
                                                <span class="text-center w-8">{{ $row->quantity }}</span>
                                                <button class="border rounded-md py-2 px-4 ml-2 btn-icon dark:text-gray-400 dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" name="plus">+</button>
                                            </form>
                                        @else
                                            <span class="text-center w-8">{{ $row->quantity }}</span>
                                        @endif
                                    </td>
                                <td class="py-4">{{ formatted_price($row->total(), $row->currency) }}</td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mt-4 dark:text-gray-400">
                            <h2 class="text-lg font-semibold dark:text-gray-300 mb-4">{{ __('coupon.add_coupon_title') }}</h2>
                            <form method="POST" action="{{ route('front.store.basket.coupon') }}">
                                @csrf
                                <div class="grid grid-cols-6 gap-2">
                                    <div class="col-span-5">
                                        @include('shared/input', ['name' => 'coupon', 'attributes' => ['placeholder' => __('coupon.coupon_placeholder')], 'value' => old('coupon', $basket->coupon ? $basket->coupon->code : null)])
                                    </div>
                                    @if ($basket->coupon)
                                        @method('DELETE')
                                    @endif
                                    <div>
                                        @if ($basket->coupon)
                                            <button type="submit" class="mt-2 btn-danger py-3 px-4 w-full"><i class="bi bi-x-circle mr-3"></i> <span class="md:inline-block hidden">{{ __('coupon.remove_coupon') }}</span></button>
                                        @else
                                            <button type="submit" class="mt-2 btn-primary py-3 px-4 w-full"><i class="bi bi-ticket-perforated mr-3"></i>
                                                <span class="md:inline-block hidden">{{ __('coupon.add_coupon') }}</span></button>
                                        @endif
                                    </div>

                                </div>
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
                                        @if ($basket->coupon->free_setup == 1 && $basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES) > 0)
                                            <div class="flex justify-between mb-2">
                                                <span>{{ __('coupon.free_setup') }}</span>
                                                <span id="free_setup" class="text-primary">-{{ formatted_price($basket->discount(\App\Models\Store\Basket\BasketRow::SETUP_FEES), $basket->currency()) }}</span>
                                            </div>
                                        @endif
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
                                <span>{{ __('store.config.onetime_payment') }}</span>
                                <span id="onetime">{{ formatted_price($basket->onetimePayment(), $basket->currency()) }}</span>
                            </div>
                                <div class="flex justify-between mb-2">
                                    <span>{{ __('store.fees') }}</span>
                                    <span id="fees">{{ formatted_price($basket->setup(), $basket->currency()) }}</span>
                                </div>
                                <div class="flex justify-between mb-2">
                                    <span>{{ __('store.vat') }}</span>
                                    <span id="taxes">{{ formatted_price($basket->tax(), $basket->currency()) }}</span>
                                </div>
                                <div class="flex justify-between mb-2">
                                    <span>{{ $basket->coupon ? __('coupon.subtotal_without_coupon') : __('store.subtotal') }}</span>
                                    <span id="subtotal">{{ formatted_price($basket->subtotalWithoutCoupon(), $basket->currency()) }}</span>
                                </div>
                            <hr class="my-2">
                            <div class="flex justify-between mb-2">
                                <span class="font-semibold">{{ __('store.basket.paytoday') }}</span>
                                <span class="font-semibold" id="total">{{ formatted_price($basket->total(), $basket->currency()) }}</span>
                            </div>
                        </div>
                        <a href="{{ route('front.store.basket.checkout') }}" class="btn-primary py-2 px-4 mt-4 w-full block text-center">{{ __('store.basket.finish') }}</a>


                    </div>
            </div>
    </div>
    {!! render_theme_sections() !!}


@endsection
