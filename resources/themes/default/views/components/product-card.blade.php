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
@props(['product', 'billing' => null, 'basket_url' => null, 'basket_title' => null])

@php
    $pricing = $product['prices'][0]; // Assuming first price is the default, will need adjustment if multiple pricings
    $showSetup = $pricing['has_setup'] && ($showSetup ?? true);
@endphp

<div class="flex flex-col border border-gray-200 text-center shadow-sm rounded-xl p-8 dark:border-gray-700 hover:scale-105 transition-transform duration-200"
     :class="product.pinned ? 'border-2 border-blue-600 shadow-xl dark:border-blue-700' : ''">
    <template x-if="product.pinned">
        <p class="mb-3"><span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-lg text-xs uppercase font-semibold bg-blue-100 text-blue-800 dark:bg-blue-600 dark:text-white"
                              x-text="product.metadata.pinned_label || '{{ __('store.mustpopular') }}'"></span></p>
    </template>

    <template x-if="product.image">
        <img :src="product.image_url" :alt="product.name" class="w-16 h-16 mx-auto rounded-lg mb-2">
    </template>
    <h4 class="font-medium text-lg text-gray-800 dark:text-gray-200" x-text="product.name"></h4>

    <template x-if="product.is_personalized">
        <span class="mt-5 font-bold text-5xl text-gray-800 dark:text-gray-200">
            {{ __('store.product.personalized') }}
        </span>
    </template>
    <template x-if="!product.is_personalized">
        <template x-if="pricing.is_free">
            <span class="mt-5 font-bold text-5xl text-gray-800 dark:text-gray-200">
                {{ __('global.free') }}
            </span>
        </template>
        <template x-if="!pricing.is_free">
            <span class="mt-5 font-bold text-5xl text-gray-800 dark:text-gray-200">
                <span x-text="pricing.price_display"></span>
                <span class="font-bold text-2xl -me-2" x-text="`${pricing.symbol} ${pricing.tax_title}`"></span>
            </span>
            <template x-if="showSetup">
                <p class="mt-2 text-sm text-gray-500" x-text="pricing.pricing_message"></p>
            </template>
        </template>
    </template>

    <ul class="mt-7 space-y-2.5 text-sm" x-html="product.description"></ul>

    <template x-if="product.is_out_of_stock">
        <button class="btn-product-pinned">
            {{ __('store.product.outofstock') }}
            @include("shared.icons.slash")
        </button>
    </template>
    <template x-if="!product.is_out_of_stock">
        <a :href="basket_url || product.basket_url" :class="product.pinned ? 'btn-product-pinned' : 'btn-product'" class="py-2 px-4 mt-4 w-full block text-center">
            <span x-text="basket_title || product.basket_title"></span>
            @include("shared.icons.array-right")
        </a>
    </template>
</div>
