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
@php($locales = \App\Services\Core\LocaleService::getLocales(true, true))

<div
    x-data="{
        isOpen: false,
        currentItem: null,
        currentItemId: null,
        tempTranslations: {}
    }"
    x-on:open-translation-modal.window="
        currentItem = $event.detail.item;
        currentItemId = $event.detail.itemId;
        tempTranslations = JSON.parse(JSON.stringify(currentItem.translations || {}));
        isOpen = true;
    "
>
    {{-- Overlay backdrop --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/50 z-[79]"
        @click="isOpen = false"
    ></div>

    {{-- Slide-over panel --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed top-0 end-0 h-full max-w-lg w-full z-[80] bg-white dark:bg-gray-800 border-s dark:border-gray-700 shadow-xl overflow-y-auto"
    >
        {{-- Header --}}
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                {{ __('admin.locales.subheading') }}
            </h3>
            <button
                type="button"
                @click="isOpen = false"
                class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700"
            >
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"/>
                    <path d="m6 6 12 12"/>
                </svg>
            </button>
        </div>

        {{-- Content --}}
        <div class="p-4" x-show="currentItem">
            {{-- Current item info --}}
            <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('personalization.menu_links.translating') }}:
                    <span class="font-semibold text-gray-800 dark:text-white" x-text="currentItem?.name"></span>
                </p>
            </div>

            {{-- Translation fields by locale --}}
            @foreach($locales as $localeKey => $locale)
                <div class="mb-6">
                    <h4 class="font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                        @if(isset($locale['flag']))
                            <span>{!! $locale['flag'] !!}</span>
                        @endif
                        {{ $locale['name'] }}
                    </h4>

                    {{-- Name translation --}}
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('global.name') }}
                        </label>
                        <input
                            type="text"
                            x-model="tempTranslations['{{ $localeKey }}'] = tempTranslations['{{ $localeKey }}'] || {}; tempTranslations['{{ $localeKey }}']['name']"
                            :value="tempTranslations['{{ $localeKey }}']?.name || ''"
                            @input="if (!tempTranslations['{{ $localeKey }}']) tempTranslations['{{ $localeKey }}'] = {}; tempTranslations['{{ $localeKey }}'].name = $event.target.value"
                            class="input-text text-sm"
                            placeholder="{{ __('global.name') }}"
                        >
                    </div>

                    {{-- URL translation --}}
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('global.url') }}
                        </label>
                        <input
                            type="text"
                            :value="tempTranslations['{{ $localeKey }}']?.url || ''"
                            @input="if (!tempTranslations['{{ $localeKey }}']) tempTranslations['{{ $localeKey }}'] = {}; tempTranslations['{{ $localeKey }}'].url = $event.target.value"
                            class="input-text text-sm"
                            placeholder="{{ __('global.url') }}"
                        >
                    </div>

                    {{-- Badge translation --}}
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('personalization.badge') }}
                        </label>
                        <input
                            type="text"
                            :value="tempTranslations['{{ $localeKey }}']?.badge || ''"
                            @input="if (!tempTranslations['{{ $localeKey }}']) tempTranslations['{{ $localeKey }}'] = {}; tempTranslations['{{ $localeKey }}'].badge = $event.target.value"
                            class="input-text text-sm"
                            placeholder="{{ __('personalization.badge') }}"
                        >
                    </div>

                    {{-- Description translation --}}
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('global.description') }}
                        </label>
                        <input
                            type="text"
                            :value="tempTranslations['{{ $localeKey }}']?.description || ''"
                            @input="if (!tempTranslations['{{ $localeKey }}']) tempTranslations['{{ $localeKey }}'] = {}; tempTranslations['{{ $localeKey }}'].description = $event.target.value"
                            class="input-text text-sm"
                            placeholder="{{ __('global.description') }}"
                        >
                    </div>
                </div>
            @endforeach

            {{-- Actions --}}
            <div class="flex gap-2 mt-6">
                <button
                    type="button"
                    @click="
                        if (currentItem) {
                            currentItem.translations = tempTranslations;
                            hasChanges = true;
                        }
                        isOpen = false;
                    "
                    class="btn btn-primary"
                >
                    <i class="bi bi-check-lg mr-1"></i>
                    {{ __('global.apply') }}
                </button>
                <button
                    type="button"
                    @click="isOpen = false"
                    class="btn btn-secondary"
                >
                    {{ __('global.cancel') }}
                </button>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                {{ __('personalization.menu_links.translation_note') }}
            </p>
        </div>
    </div>
</div>
