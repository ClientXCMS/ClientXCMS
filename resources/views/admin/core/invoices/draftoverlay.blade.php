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


<div id="item-draftitem" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">

    <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
        <h3 class="font-bold text-gray-800 dark:text-white">
            {{ __('admin.invoices.draft.add') }}
        </h3>
        <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#item-draftitem">
            <span class="sr-only">{{ __('global.closemodal') }}</span>
            <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>
    <div class="p-4" id="item-content">

    </div>
</div>
@foreach($invoice->items as $item)
    @php($related = $item->relatedType())

    <div id="edititem-{{ $item->id }}" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">

        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                {{ __('admin.invoices.draft.edit') }}
            </h3>
            <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#edititem-{{ $item->id }}">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="p-4" id="item-content-{{ $item->id }}">
            <form method="POST" action="{{ route($routePath . '.updateitem', ['invoiceItem' => $item, 'invoice' => $invoice]) }}">
                @csrf
                @method('PATCH')
                @if ($related instanceof \App\Models\Store\Product)
                    @php($pricings = $related->pricingAvailable())
                    <div class="grid sm:grid-cols-2 gap-2 basket-billing-section">
                        @foreach ($pricings as $pricing)
                            <label for="billing-{{ $pricing->recurring }}-{{ $pricing->currency }}" class="col-span-3 md:col-span-1 p-3 block w-full bg-white border border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                                <span class="dark:text-gray-400 font-semibold">@if ($pricing->isFree()){{ __('global.free') }} @else {{ $pricing->getPriceByDisplayMode() }} {{ $pricing->getSymbol() }} @endif {{ $pricing->recurring()['translate'] }}<p class="text-gray-500">{{ $pricing->pricingMessage() }}</p></span>
                                <input type="radio" name="billing" value="{{ $pricing->recurring }}" {{ ($item->billing() == $pricing->recurring) || $loop->first ? 'checked' : '' }} data-pricing="{{ $pricing->toJson()  }}" class="shrink-0 ms-auto mt-0.5 border-gray-200 rounded-full text-indigo-600 focus:ring-indigo-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-indigo-600 dark:checked:border-indigo-600 dark:focus:ring-offset-gray-800" id="billing-{{ $pricing->recurring }}-{{ $pricing->currency }}">
                            </label>
                        @endforeach
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col col-span-2">
                        @include('admin/shared/input', ['name' => 'name', 'label' => __($translatePrefix . '.draft.name'), 'value' => $item->name])
                    </div>
                    <div>
                        @include('admin/shared/input', ['name' => 'quantity', 'label' => __($translatePrefix . '.draft.quantity'), 'value' => $item->quantity, 'min' => 1, 'step' => 1])
                    </div>
                    <div class="col-span-2">
                        @include('admin/shared/input', ['name' => 'unit_price_ttc', 'label' => __($translatePrefix . '.draft.unitprice') . ' (TTC)', 'value' => $item->unit_price_ttc ?? 0, 'min' => 0, 'step' => 0.01])
                    </div>

                    <div>
                        @include('admin/shared/input', ['name' => 'unit_price_ht', 'label' => __($translatePrefix . '.draft.unitprice') . ' (HT)', 'value' => $item->unit_price_ht ?? 0, 'min' => 0, 'step' => 0.01])
                    </div>
                    <div class="col-span-2">
                        @include('admin/shared/input', ['name' => 'unit_setup_ttc', 'label' => __($translatePrefix . '.draft.unitsetupfees') . ' (TTC)', 'value' => $item->unit_setup_ttc ?? 0, 'min' => 0, 'step' => 0.01])
                    </div>
                    <div>
                        @include('admin/shared/input', ['name' => 'unit_setup_ht', 'label' => __($translatePrefix . '.draft.unitsetupfees') . ' (HT)', 'value' => $item->unit_setup_ht ?? 0, 'min' => 0, 'step' => 0.01])
                    </div>
                    <div class="col-span-3">
                        @include('shared/textarea', ['name' => 'description', 'label' => __($translatePrefix . '.draft.description'), 'value' => $item->description ?? '', 'rows' => 3])
                    </div>
                </div>
                    <div>
                        @include('admin/shared/select', ['name' => 'coupon_id', 'label' => __('coupon.coupons'), 'options' => $coupons, 'value' => $item->couponId() ?? 'none'])
                    </div>
            {!! $item->renderHTML(true) !!}

                <button class="btn btn-primary mt-4">
                    {{ __('admin.invoices.draft.edit') }}
                </button>
            </form>
        </div>
    </div>
    @endforeach
<button type="button" id="btn-draftitem" class="hidden" data-hs-overlay="#item-draftitem">
</button>

