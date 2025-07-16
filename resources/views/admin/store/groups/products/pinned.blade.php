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
@php($pricing = $product->getPriceByCurrency(currency()))

<div class="flex flex-col border-2 border-blue-600 text-center shadow-xl rounded-xl p-8 dark:border-blue-700 dark:bg-slate-900 mb-3">
    <p class="mb-3"><span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-lg text-xs uppercase font-semibold bg-blue-100 text-blue-800 dark:bg-blue-600 dark:text-white">Most popular</span></p>
    <div class="hs-dropdown top-3 left-3 z-10">

        <button id="hs-dropdown-custom-icon-trigger" type="button" class="hs-dropdown-toggle p-2 flex justify-center items-center size-9 text-sm font-semibold rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-neutral-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:bg-gray-800" aria-haspopup="menu" aria-expanded="false" aria-label="Dropdown">
            <i class="bi bi-three-dots"></i>
        </button>
        <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-md rounded-lg p-1 space-y-0.5 mt-2 dark:bg-gray-800 dark:border dark:border-gray-700" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-custom-icon-trigger">
            <a class="flex items-center gap-x-3.5 py-2 w-full px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:bg-neutral-700" href="{{ route('admin.products.show', ['product' => $product]) }}">
                <i class="bi bi-eye-fill"></i>
                {{ __('global.show') }}
            </a>
            <form method="POST" action="{{ route('admin.products.clone', ['product' => $product]) }}">
                @csrf
                @method('PUT')
                <button class="flex items-center gap-x-3.5 py-2 w-full px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:bg-neutral-700">
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    {{ __('global.clone') }}
                    </span>
                </button>
            </form>

            <form method="POST" action="{{ route('admin.products.destroy', ['product' => $product]) }}">
                @method('DELETE')
                @csrf
                <button class="flex items-center w-full gap-x-3.5 py-2 px-3 rounded-lg text-sm text-red-800 hover:bg-red-100 focus:outline-none focus:bg-gray-100 dark:text-red-400 dark:hover:bg-red-700 dark:hover:text-red-300 dark:focus:bg-grey-700">
                    <i class="bi bi-trash"></i>
                    {{ __('global.delete') }}
                </button>
            </form>
        </div>
    </div>
    <h4 class="font-medium text-lg text-gray-800 dark:text-gray-200">{{ $product->name }}</h4>
    @if ($product->isPersonalized())
    <span class="mt-5 font-bold text-5xl text-gray-800 dark:text-gray-200">
        {{ __('store.product.personalized') }}
        </span>
    @elseif ($pricing->isFree())
            <span class="mt-5 font-bold text-5xl text-gray-800 dark:text-gray-200">
        {{ __('global.free') }}
      </span>
    @else
        <span class="mt-5 font-bold text-5xl text-gray-800 dark:text-gray-200">
        {{ $pricing->price }}
        <span class="font-bold text-2xl -me-2">{{ $pricing->getSymbol() }} {{ $pricing->taxTitle() }}</span>

      </span>

        @if ($pricing->hasSetup())
            <p class="mt-2 text-sm text-gray-500">{{ $pricing->pricingMessage() }}</p>
        @endif
    @endif
    <ul class="mt-7 space-y-2.5 text-sm">
        {!!  $product->description !!}
    </ul>

    <div class="mt-2 flex">
        <input type="text" readonly class="input-text" id="product-{{ $product->id }}" value="{{ route('front.store.basket.add', ['product' => $product]) }}">
        <button type="button" data-clipboard-target="#product-{{ $product->id }}" data-clipboard-action="copy" data-clipboard-success-text="Copied" class=" js-clipboard w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent bg-blue-600 text-white hover:bg-blue-700  dark:focus:ring-1 dark:focus:ring-gray-600">
            <svg class="js-clipboard-default w-4 h-4 group-hover:rotate-6 transition" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>

            <svg class="js-clipboard-success hidden w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>

        </button>
        <a href="{{ route('admin.products.show', ['product' => $item]) }}" class="js-clipboard w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent bg-primary text-white  dark:focus:ring-1 dark:focus:ring-gray-600">
            <i class="bi bi-eye-fill"></i>
        </a>
    </div>
</div>
