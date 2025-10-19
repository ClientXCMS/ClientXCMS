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

<form method="POST" action="{{ route($routePath .'.draft', ['invoice' => $invoice])  }}">
    @csrf
    @if ($service != null && $service->invoice_id)

            <div class="alert text-yellow-800 bg-yellow-100 mt-2 mb-2" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                {{ __('admin.invoices.draft.invoiceserviceinfo', ['id' => $service->invoice_id]) }}
            </div>
    @endif
    @if ($product != null)
    @php($pricings = $product->pricingAvailable())
    <div class="grid sm:grid-cols-2 gap-2 basket-billing-section">
        @foreach ($pricings as $pricing)
            <label for="billing-{{ $pricing->recurring }}-{{ $pricing->currency }}" class="col-span-3 md:col-span-1 p-3 block w-full bg-white border border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                <span class="dark:text-gray-400 font-semibold">@if ($pricing->isFree()){{ __('global.free') }} @else {{ $pricing->getPriceByDisplayMode() }} {{ $pricing->getSymbol() }} @endif {{ $pricing->recurring()['translate'] }}<p class="text-gray-500">{{ $pricing->pricingMessage() }}</p></span>
                <input type="radio" name="billing" value="{{ $pricing->recurring }}" {{ ($billing == $pricing->recurring) || $loop->first ? 'checked' : '' }} data-pricing="{{ $pricing->toJson()  }}" class="shrink-0 ms-auto mt-0.5 border-gray-200 rounded-full text-indigo-600 focus:ring-indigo-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-indigo-600 dark:checked:border-indigo-600 dark:focus:ring-offset-gray-800" id="billing-{{ $pricing->recurring }}-{{ $pricing->currency }}">
            </label>
        @endforeach
    </div>
    @endif
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="flex flex-col col-span-2">
            @include('admin/shared/input', ['name' => 'name', 'label' => __($translatePrefix . '.draft.name'), 'value' => $service != null ? $service->getInvoiceName() : $product->name ?? __($translatePrefix . '.customproduct')])
        </div>
        <div>
            @include('admin/shared/input', ['name' => 'quantity', 'label' => __($translatePrefix . '.draft.quantity'), 'value' => $product->quantity ?? 1, 'min' => 1, 'step' => 1])
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            @include('admin/shared/input', ['name' => 'unit_price_ttc', 'label' => __($translatePrefix . '.draft.unitprice') . ' (TTC)', 'value' => $product->unit_price_ttc ?? 0, 'min' => 0, 'step' => 0.01])
        </div>
        <div>
            @include('admin/shared/input', ['name' => 'unit_setup_ttc', 'label' => __($translatePrefix . '.draft.unitsetupfees') . ' (TTC)', 'value' => $product->unit_setup_ttc ?? 0, 'min' => 0, 'step' => 0.01])
        </div>
        <div class="col-span-3">
            @include('admin/shared/textarea', ['name' => 'description', 'label' => __($translatePrefix . '.draft.description'), 'value' => $product->formattedDescription() ?? ($service->description ?? ''), 'rows' => 3])
        </div>
    </div>
    <input type="hidden" name="related" value="{{ $relatedId == 'none' ? 'custom_item' : $related }}">
    <input type="hidden" name="related_id" value="{{ $relatedId == 'none' ? 0 : $relatedId }}">
    @if (!empty($dataHTML))
    {!! $dataHTML !!}
    @endif

    <button class="btn btn-primary mt-4">
        {{ __('admin.invoices.draft.add') }}
    </button>
</form>
