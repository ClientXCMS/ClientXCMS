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
@extends('admin.settings.sidebar')
@section('title', __('extensions.settings.title'))

@php
$allExtensions = collect($groups)->flatMap(fn($group) => $group['items']);
$installedExtensions = $allExtensions->filter(fn($ext) => $ext->isInstalled());
$themes = $allExtensions->filter(fn($ext) => $ext->type === 'theme');
$installedThemes = $themes->filter(fn($ext) => $ext->isInstalled());
$installedModulesAddons = $installedExtensions->filter(fn($ext) => $ext->type !== 'theme');
$newExtensions = $allExtensions->filter(fn($ext) => isset($ext->api['tags']) && collect($ext->api['tags'])->contains('slug', 'new'))->take(4);
$popularExtensions = $allExtensions->filter(fn($ext) => isset($ext->api['tags']) && collect($ext->api['tags'])->contains('slug', 'popular'))->take(4);
$apiDegraded = $apiDegraded ?? false;
@endphp

@section('setting')
<div class="container mx-auto px-4 sm:px-6 lg:px-8">

    {{-- API Degradation Warning Banner (Story 2.4) --}}
    @if ($apiDegraded)
    <div id="js-api-banner" class="mb-6 flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl text-amber-800 dark:text-amber-300" role="alert">
        <i class="bi bi-exclamation-triangle-fill text-xl flex-shrink-0"></i>
        <div class="flex-1">
            <p class="font-medium">{{ __('extensions.settings.api_degraded_title') }}</p>
            <p class="text-sm text-amber-700 dark:text-amber-400">{{ __('extensions.settings.api_degraded_desc') }}</p>
        </div>
        <button type="button" class="flex-shrink-0 text-amber-500 hover:text-amber-700 dark:hover:text-amber-200 transition-colors" onclick="this.closest('#js-api-banner').remove()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    @endif

    {{-- Aria-live announcer regions --}}
    <div id="js-extension-announcer" class="sr-only" aria-live="polite" aria-atomic="true"></div>
    <div id="js-search-announcer" class="sr-only" aria-live="polite" aria-atomic="true"></div>

    <div class="relative overflow-hidden rounded-2xl bg-primary to-pink-500 p-8 mb-8 shadow-2xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                        <i class="bi bi-grid-3x3-gap-fill mr-3"></i>{{ __('extensions.settings.title') }}
                    </h1>
                    <p class="text-white/80 text-lg max-w-xl">{{ __('extensions.settings.description') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Story 3.6: Bulk Select Toggle --}}
                    @if($installedExtensions->count() > 0)
                    <button type="button"
                        data-action="toggle-bulk-mode"
                        class="flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 text-white rounded-xl font-medium transition-all duration-200 backdrop-blur-sm border border-white/20 text-sm">
                        <i class="bi bi-check2-square mr-1"></i> Sélectionner
                    </button>
                    @endif

                    {{-- Story 3.1: Cart Badge Button --}}
                    <button type="button"
                        data-action="open-cart"
                        class="relative flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 text-white rounded-xl font-medium transition-all duration-200 backdrop-blur-sm border border-white/20"
                        aria-label="Panier, 0 extension sélectionnée">
                        <i class="bi bi-cart3 text-lg"></i>
                        <span id="cart-badge"
                            class="hidden absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-lg transition-transform"
                            aria-hidden="true">0</span>
                    </button>

                    <form action="{{ route('admin.settings.extensions.clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 px-5 py-2.5 bg-white/20 hover:bg-white/30 text-white rounded-xl font-medium transition-all duration-200 backdrop-blur-sm border border-white/20">
                            <i class="bi bi-arrow-clockwise"></i> {{ __('extensions.settings.clearcache') }}
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- Search Bar (Story 2.2) - sticky on mobile, static on desktop --}}
    <div class="sticky top-0 z-30 lg:static lg:z-auto py-3 lg:py-0 mb-4 lg:mb-8 bg-white dark:bg-slate-950 lg:bg-transparent lg:dark:bg-transparent shadow-sm lg:shadow-none border-b border-gray-200 dark:border-slate-800 lg:border-0" role="search" aria-label="{{ __('extensions.settings.search_label') }}">
        <div class="relative max-w-2xl">
            <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
            <input
                type="text"
                id="extension-search"
                class="w-full pl-12 pr-12 lg:pr-20 py-3.5 lg:py-4 bg-gray-50 dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-white placeholder-gray-500 shadow-sm focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-300 dark:focus:border-indigo-600 transition-all duration-200"
                placeholder="{{ __('extensions.settings.search_placeholder') }}"
                aria-label="{{ __('extensions.settings.search_placeholder') }}">
            <button type="button" id="js-search-clear" class="hidden absolute right-4 lg:right-12 top-1/2 -translate-y-1/2 p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors" aria-label="{{ __('extensions.settings.clear_search') }}">
                <i class="bi bi-x-lg"></i>
            </button>
            <kbd class="hidden lg:inline-flex absolute right-4 top-1/2 -translate-y-1/2 items-center px-2 py-1 text-xs font-mono text-gray-400 bg-gray-100 dark:bg-slate-700 rounded border border-gray-300 dark:border-slate-600">/</kbd>
        </div>
    </div>

    {{-- Story 3.5: Update Banner --}}
    @if (!empty($groups))
    @include('admin.settings.extensions._update-banner')
    @endif

    @if (!empty($groups))
    <div class="mb-8">
        <div class="flex flex-wrap items-center gap-2 p-1.5 bg-gray-100 dark:bg-slate-800 rounded-xl w-fit">
            <button class="main-tab-btn active px-6 py-3 rounded-lg text-sm font-semibold transition-all duration-200 bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm" data-tab="installed">
                <i class="bi bi-collection-fill mr-2"></i>{{ __('extensions.settings.tabs.my_extensions') }}
                <span class="ml-2 px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-full text-xs" aria-live="polite"><span class="js-tab-counter">{{ $installedExtensions->count() }}</span></span>
            </button>
            <button class="main-tab-btn px-6 py-3 rounded-lg text-sm font-semibold transition-all duration-200 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400" data-tab="discover">
                <i class="bi bi-compass-fill mr-2"></i>{{ __('extensions.settings.tabs.discover') }}
                <span class="ml-2 px-2 py-0.5 bg-gray-200 dark:bg-slate-600 text-gray-600 dark:text-gray-400 rounded-full text-xs" aria-live="polite"><span class="js-tab-counter">{{ $allExtensions->count() }}</span></span>
            </button>
            <button class="main-tab-btn px-6 py-3 rounded-lg text-sm font-semibold transition-all duration-200 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400" data-tab="themes">
                <i class="bi bi-palette-fill mr-2"></i>{{ __('extensions.settings.tabs.themes') }}
                <span class="ml-2 px-2 py-0.5 bg-gray-200 dark:bg-slate-600 text-gray-600 dark:text-gray-400 rounded-full text-xs" aria-live="polite"><span class="js-tab-counter">{{ $themes->count() }}</span></span>
            </button>
        </div>
    </div>

    <div id="tab-installed" class="tab-content">
        @if ($installedExtensions->count() > 0)
        @if ($installedThemes->count() > 0)
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-500 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="bi bi-palette-fill text-white text-lg"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('extensions.settings.sections.my_themes') }}</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">({{ $installedThemes->count() }})</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach ($installedThemes as $extension)
                @include('admin.settings.extensions._card', ['extension' => $extension, 'groupName' => 'themes'])
                @endforeach
            </div>
        </div>
        @endif

        @if ($installedModulesAddons->count() > 0)
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-blue-500 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="bi bi-puzzle-fill text-white text-lg"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('extensions.settings.sections.my_modules') }}</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">({{ $installedModulesAddons->count() }})</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" id="installed-grid">
                @foreach ($installedModulesAddons as $extension)
                @include('admin.settings.extensions._card', ['extension' => $extension, 'groupName' => $extension->api['group_uuid'] ?? 'unknown'])
                @endforeach
            </div>
        </div>
        @endif

        {{-- No search results (Story 2.2) --}}
        <div class="js-no-results hidden text-center py-16 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
            <i class="bi bi-search text-5xl text-gray-300 dark:text-slate-600"></i>
            <p class="mt-4 text-gray-500 dark:text-gray-400">{{ __('extensions.settings.no_results') }}</p>
        </div>
        @else
        <div class="text-center py-20 bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700">
            <div class="w-20 h-20 bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="bi bi-box-seam text-4xl text-gray-400 dark:text-slate-500"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ __('extensions.settings.no_installed') }}</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('extensions.settings.no_installed_desc') }}</p>
            <button class="main-tab-btn px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition-colors" data-tab="discover">
                <i class="bi bi-compass mr-2"></i>{{ __('extensions.settings.discover_extensions') }}
            </button>
        </div>
        @endif
    </div>

    <div id="tab-discover" class="tab-content hidden">
        <div class="mb-8">
            <div class="flex flex-wrap gap-2 items-center">
                <button class="group-filter-btn active px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 bg-indigo-600 text-white shadow-md" data-group="all">
                    <i class="bi bi-grid-fill mr-1"></i> {{ __('global.states.all') }}
                </button>
                @foreach ($groups as $groupName => $groupData)
                @php $groupIcon = $groupData['icon']; $extensions = $groupData['items']; @endphp
                <button class="group-filter-btn px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 hover:text-indigo-600 dark:hover:text-indigo-400" data-group="{{ Str::slug($groupName) }}">
                    <i class="{{ $groupIcon }} mr-1"></i> {{ $groupName }}
                    <span class="ml-1 text-xs opacity-70">({{ count($extensions) }})</span>
                </button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
            @if ($newExtensions->count() > 0)
            <div id="section-new">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="bi bi-newspaper text-white text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('extensions.settings.sections.new') }}</h2>
                </div>
                <div class="space-y-3">
                    @foreach ($newExtensions as $extension)
                    @include('admin.settings.extensions._card-compact', ['extension' => $extension])
                    @endforeach
                </div>
            </div>
            @endif

            @if ($popularExtensions->count() > 0)
            <div id="section-popular">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-rose-400 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="bi bi-fire text-white text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('extensions.settings.sections.popular') }}</h2>
                </div>
                <div class="space-y-3">
                    @foreach ($popularExtensions as $extension)
                    @include('admin.settings.extensions._card-compact', ['extension' => $extension])
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="mb-6">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="bi bi-grid-3x3-gap text-white text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('extensions.settings.sections.all_extensions') }}</h2>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400" id="extensions-count">
                    {{ $allExtensions->count() }} {{ __('extensions.settings.extensions_count') }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" id="extensions-grid">
            @foreach ($groups as $groupName => $groupData)
            @foreach ($groupData['items'] as $extension)
            @include('admin.settings.extensions._card', ['extension' => $extension, 'groupName' => $groupName])
            @endforeach
            @endforeach
        </div>

        <div id="no-results" class="js-no-results hidden text-center py-16 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
            <i class="bi bi-search text-5xl text-gray-300 dark:text-slate-600"></i>
            <p class="mt-4 text-gray-500 dark:text-gray-400">{{ __('extensions.settings.no_results') }}</p>
        </div>
    </div>

    <div id="tab-themes" class="tab-content hidden">
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-500 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="bi bi-palette-fill text-white text-lg"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('extensions.settings.sections.all_themes') }}</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">({{ $themes->count() }} {{ __('extensions.settings.themes_available') }})</span>
            </div>
        </div>

        @if ($themes->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" id="themes-grid">
            @foreach ($themes as $extension)
            @include('admin.settings.extensions._card-theme', ['extension' => $extension])
            @endforeach
        </div>
        @else
        <div class="text-center py-16 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
            <i class="bi bi-palette text-5xl text-gray-300 dark:text-slate-600"></i>
            <p class="mt-4 text-gray-500 dark:text-gray-400">{{ __('extensions.settings.no_themes') }}</p>
        </div>
        @endif

        {{-- No search results (Story 2.2) --}}
        <div class="js-no-results hidden text-center py-16 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
            <i class="bi bi-search text-5xl text-gray-300 dark:text-slate-600"></i>
            <p class="mt-4 text-gray-500 dark:text-gray-400">{{ __('extensions.settings.no_results') }}</p>
        </div>
    </div>
    @else
        @if ($apiDegraded)
        <div class="text-center py-16 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
            <i class="bi bi-cloud-slash text-5xl text-amber-400"></i>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('extensions.settings.api_unavailable_title') }}</h3>
            <p class="mt-2 text-gray-500 dark:text-gray-400">{{ __('extensions.settings.api_unavailable_desc') }}</p>
        </div>
        @else
        <div class="text-center py-16 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
            <i class="bi bi-puzzle text-5xl text-gray-300 dark:text-slate-600"></i>
            <p class="mt-4 text-gray-500 dark:text-gray-400">{{ __('extensions.settings.no_extensions_found') }}</p>
        </div>
        @endif
    @endif

    {{-- Story 3.3/3.4: Batch Progress + Recap --}}
    @include('admin.settings.extensions._batch-progress')
    @include('admin.settings.extensions._batch-recap')
</div>

{{-- Product Detail Modal (Story 2.3) --}}
<div id="js-extension-modal" class="hidden fixed inset-0 z-[80] overflow-y-auto" role="dialog" aria-modal="true" aria-label="{{ __('extensions.settings.product_sheet') }}">
    <div class="min-h-screen px-4 py-8 flex items-center justify-center">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/60 dark:bg-black/70 transition-opacity"></div>

        {{-- Modal panel --}}
        <div class="js-modal-panel relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                <h2 class="js-modal-title text-xl font-bold text-gray-900 dark:text-white"></h2>
                <button type="button" class="js-modal-close p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Thumbnail --}}
                    <div class="js-modal-thumbnail aspect-video rounded-xl overflow-hidden bg-gray-100 dark:bg-slate-800"></div>

                    {{-- Metadata --}}
                    <div class="space-y-4">
                        {{-- Author --}}
                        <div class="flex items-center gap-3">
                            <div class="js-modal-author-avatar"></div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('extensions.settings.modal.author') }}</p>
                                <p class="js-modal-author font-medium text-gray-900 dark:text-white"></p>
                            </div>
                        </div>

                        {{-- Rating --}}
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('extensions.settings.modal.rating') }}</p>
                            <div class="js-modal-rating flex items-center gap-0.5"></div>
                        </div>

                        {{-- Version --}}
                        <div class="flex items-center gap-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('extensions.settings.modal.version') }}</p>
                                <p class="js-modal-version font-mono text-sm text-gray-900 dark:text-white"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('extensions.settings.modal.updated') }}</p>
                                <p class="js-modal-updated text-sm text-gray-900 dark:text-white"></p>
                            </div>
                        </div>

                        {{-- Price --}}
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('extensions.settings.modal.price') }}</p>
                            <p class="js-modal-price text-xl font-bold text-gray-900 dark:text-white"></p>
                        </div>

                        {{-- Tags --}}
                        <div class="js-modal-tags flex flex-wrap gap-1"></div>

                        {{-- Documentation --}}
                        <a href="#" target="_blank" class="js-modal-doc-link hidden inline-flex items-center gap-1.5 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                            <i class="bi bi-book"></i> {{ __('extensions.settings.modal.documentation') }}
                        </a>
                    </div>
                </div>

                {{-- Description --}}
                <div class="mt-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('extensions.settings.modal.description') }}</h3>
                    <p class="js-modal-description text-gray-600 dark:text-gray-400 leading-relaxed"></p>
                </div>
            </div>

            {{-- Sticky Footer with Actions --}}
            <div class="js-modal-actions flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50"></div>
        </div>
    </div>
</div>

{{-- Story 3.1: Cart Drawer (position: fixed, outside container) --}}
@include('admin.settings.extensions._cart-drawer')

{{-- Story 3.6: Bulk Action Bar (position: fixed/sticky) --}}
@include('admin.settings.extensions._bulk-action-bar')

@endsection

@section('scripts')
{{-- Extension data for JS batch operations --}}
@php
$extensionsData = $allExtensions->mapWithKeys(function($ext) {
    return [$ext->uuid => [
        'uuid' => $ext->uuid,
        'name' => $ext->name(),
        'type' => $ext->type(),
        'price' => $ext->price(true),
        'thumbnail' => $ext->thumbnail(),
        'isInstalled' => $ext->isInstalled(),
        'isEnabled' => $ext->isEnabled(),
        'isActivable' => $ext->isActivable(),
        'version' => $ext->version ?? null,
        'latestVersion' => $ext->getLatestVersion(),
        'hasUpdate' => $ext->isInstalled() && $ext->getLatestVersion() && version_compare($ext->version ?? '0', $ext->getLatestVersion(), '<'),
    ]];
});
@endphp
<script>
    window.__extensionsData = @json($extensionsData);
    window.__extensionsRoutes = {
        base: '{{ url("/admin/settings/extensions") }}',
    };
</script>
@vite('resources/global/js/extensions/index.js')

{{-- Translation strings for JS modules --}}
<script>
window.__extensionTranslations = {
    processing: @json(__('extensions.settings.processing')),
    enabled: @json(__('extensions.settings.enabled')),
    installed: @json(__('extensions.settings.installed')),
    disabled: @json(__('extensions.settings.disable')),
    updated: @json(__('extensions.flash.updated')),
    enable: @json(__('extensions.settings.enable')),
    disable: @json(__('extensions.settings.disable')),
    install: @json(__('extensions.settings.install')),
    update: @json(__('extensions.settings.update')),
    buy: @json(__('extensions.settings.buy')),
    cancel: @json(__('global.cancel')),
    error: @json(__('global.error')),
    confirm_disable_title: @json(__('extensions.settings.confirm_disable_title')),
    confirm_disable_text: @json(__('extensions.settings.confirm_disable_text')),
    confirm_disable: @json(__('extensions.settings.confirm_disable')),
    license_required: @json(__('extensions.settings.license_required')),
    cannot_enable: @json(__('extensions.flash.cannot_enable')),
    error_enable: @json(__('extensions.settings.error_enable')),
    error_disable: @json(__('extensions.settings.error_disable')),
    error_install: @json(__('extensions.settings.error_install')),
    error_network: @json(__('extensions.settings.error_network')),
    error_permission: @json(__('extensions.settings.error_permission')),
    error_server: @json(__('extensions.settings.error_server')),
    error_unknown: @json(__('extensions.settings.error_unknown')),
    error_update: @json(__('extensions.settings.error_update')),
    error_validation: @json(__('extensions.settings.error_validation')),
    installed_success: @json(__('extensions.flash.installed')),
    license_needed: @json(__('extensions.settings.license_needed')),
    results_found: @json(__('extensions.settings.results_found')),
    no_reviews: @json(__('extensions.settings.no_reviews')),
    free: @json(__('extensions.settings.free')),
    product_sheet: @json(__('extensions.settings.product_sheet')),
    cart_title: @json(__('extensions.settings.cart.title')),
    cart_add: @json(__('extensions.settings.cart.add')),
    cart_in_cart: @json(__('extensions.settings.cart.in_cart')),
    cart_remove: @json(__('extensions.settings.cart.remove')),
    cart_update_label: @json(__('extensions.settings.cart.update_label')),
    batch_installing: @json(__('extensions.settings.batch_installing')),
    batch_activating: @json(__('extensions.settings.batch_activating')),
    batch_updating: @json(__('extensions.settings.batch_updating')),
    batch_progress_text: @json(__('extensions.settings.batch_progress_text')),
    batch_error_default: @json(__('extensions.settings.batch_error_default')),
    batch_success_all: @json(__('extensions.settings.batch_success_all')),
    batch_failure_all: @json(__('extensions.settings.batch_failure_all')),
    batch_partial: @json(__('extensions.settings.batch_partial')),
    batch_clearing_cache: @json(__('extensions.settings.batch_clearing_cache')),
    batch_retry: @json(__('extensions.settings.batch_retry')),
    batch_select: @json(__('extensions.settings.batch_select')),
    batch_cancel_selection: @json(__('extensions.settings.batch_cancel_selection')),
    batch_selected_count: @json(__('extensions.settings.batch_selected_count')),
    batch_updates_available: @json(__('extensions.settings.batch_updates_available')),
};
</script>

{{-- AJAX Action Handlers (Stories 1.5 & 1.6) --}}
<script>
{!! file_get_contents(resource_path('global/js/extensions/ajax-handlers.js')) !!}
</script>

{{-- Product Detail Modal (Story 2.3) --}}
<script>
{!! file_get_contents(resource_path('global/js/extensions/modal.js')) !!}
</script>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

/* Batch operations: spinner animation for bi-arrow-repeat */
.animate-spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Hide keyboard hint when clear button is visible */
#js-search-clear:not(.hidden) ~ kbd { display: none !important; }

/* Batch operations: bulk mode card highlight */
.bulk-mode-active .extension-item {
    transition: all 0.2s ease;
}
.bulk-mode-active .extension-item:has(.bulk-checkbox:checked),
.bulk-mode-active .extension-item.bulk-selected {
    outline: 2px solid rgb(99 102 241);
    outline-offset: 1px;
    box-shadow: 0 0 0 4px rgb(99 102 241 / 0.15);
}
</style>
@endsection
