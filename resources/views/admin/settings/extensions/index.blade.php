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
@endphp

@section('setting')
<div class="container mx-auto px-4 sm:px-6 lg:px-8  ">
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
                <form action="{{ route('admin.settings.extensions.clear') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 px-5 py-2.5 bg-white/20 hover:bg-white/30 text-white rounded-xl font-medium transition-all duration-200 backdrop-blur-sm border border-white/20">
                        <i class="bi bi-arrow-clockwise"></i> {{ __('extensions.settings.clearcache') }}
                    </button>
                </form>
            </div>

            {{-- Search Bar --}}
            <div class="mt-8">
                <div class="relative max-w-2xl">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    <input
                        type="text"
                        id="extension-search"
                        class="w-full pl-12 pr-4 py-4 bg-white/95 dark:bg-slate-800/95 backdrop-blur-sm rounded-xl border-0 text-gray-900 dark:text-white placeholder-gray-500 shadow-lg focus:ring-4 focus:ring-white/30 transition-all duration-200"
                        placeholder="{{ __('extensions.settings.search_placeholder') ?? 'Rechercher une extension...' }}">
                </div>
            </div>
        </div>
    </div>

    @if (!empty($groups))
    <div class="mb-8">
        <div class="flex flex-wrap items-center gap-2 p-1.5 bg-gray-100 dark:bg-slate-800 rounded-xl w-fit">
            <button class="main-tab-btn active px-6 py-3 rounded-lg text-sm font-semibold transition-all duration-200 bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm" data-tab="installed">
                <i class="bi bi-collection-fill mr-2"></i>{{ __('extensions.settings.tabs.my_extensions') }}
                <span class="ml-2 px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-full text-xs">{{ $installedExtensions->count() }}</span>
            </button>
            <button class="main-tab-btn px-6 py-3 rounded-lg text-sm font-semibold transition-all duration-200 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400" data-tab="discover">
                <i class="bi bi-compass-fill mr-2"></i>{{ __('extensions.settings.tabs.discover') }}
                <span class="ml-2 px-2 py-0.5 bg-gray-200 dark:bg-slate-600 text-gray-600 dark:text-gray-400 rounded-full text-xs">{{ $allExtensions->count() }}</span>
            </button>
            <button class="main-tab-btn px-6 py-3 rounded-lg text-sm font-semibold transition-all duration-200 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400" data-tab="themes">
                <i class="bi bi-palette-fill mr-2"></i>{{ __('extensions.settings.tabs.themes') }}
                <span class="ml-2 px-2 py-0.5 bg-gray-200 dark:bg-slate-600 text-gray-600 dark:text-gray-400 rounded-full text-xs">{{ $themes->count() }}</span>
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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" id="extensions-grid">
            @foreach ($groups as $groupName => $groupData)
            @foreach ($groupData['items'] as $extension)
            @include('admin.settings.extensions._card', ['extension' => $extension, 'groupName' => $groupName])
            @endforeach
            @endforeach
        </div>

        <div id="no-results" class="hidden text-center py-16 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="themes-grid">
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
    </div>
    @else
    <div class="text-center py-16 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
        <i class="bi bi-puzzle text-5xl text-gray-300 dark:text-slate-600"></i>
        <p class="mt-4 text-gray-500 dark:text-gray-400">{{ __('extensions.settings.no_extensions_found') }}</p>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<div id="bulk-toolbar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 hidden">
    <div class="flex items-center gap-4 px-6 py-4 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700">
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                <span id="selected-count">0</span> {{ __('extensions.bulk.selected_count', ['count' => '']) }}
            </span>
            <button id="select-all-btn" class="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium">
                {{ __('extensions.bulk.select_all') }}
            </button>
        </div>
        <div class="h-6 w-px bg-gray-300 dark:bg-slate-600"></div>
        <div class="flex items-center gap-2">
            <button id="bulk-install-btn" class="btn btn-primary btn-sm flex items-center gap-1">
                <i class="bi bi-cloud-download"></i>{{ __('extensions.bulk.install_selected') }}
            </button>
            <button id="bulk-enable-btn" class="btn btn-success btn-sm flex items-center gap-1">
                <i class="bi bi-check-circle"></i>{{ __('extensions.bulk.enable_selected') }}
            </button>
            <button id="bulk-disable-btn" class="btn btn-danger btn-sm flex items-center gap-1">
                <i class="bi bi-ban"></i>{{ __('extensions.bulk.disable_selected') }}
            </button>
        </div>
        <div class="h-6 w-px bg-gray-300 dark:bg-slate-600"></div>
        <button id="cancel-selection-btn" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
</div>
<div id="extension-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="modal-backdrop"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <button id="modal-close" class="absolute top-4 right-4 z-10 p-2 rounded-lg bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors">
                <i class="bi bi-x-lg text-gray-600 dark:text-gray-400"></i>
            </button>
            <div id="modal-content" class="overflow-y-auto max-h-[90vh]">
            </div>
        </div>
    </div>
</div>

<div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2"></div>
<script src="https://cdn.jsdelivr.net/npm/marked/lib/marked.umd.js"></script>
<script src="{{ Vite::asset('resources/global/js/admin/extensions.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new ExtensionManager({
            csrfToken: '{{ csrf_token() }}',
            routes: {
                enable: '{{ route("admin.settings.extensions.enable", ["TYPE", "UUID"]) }}',
                disable: '{{ route("admin.settings.extensions.disable", ["TYPE", "UUID"]) }}',
                update: '{{ route("admin.settings.extensions.update", ["TYPE", "UUID"]) }}',
                bulk: '{{ route("admin.settings.extensions.bulk") }}'
            },
            translations: {
                processing: '{{ __("extensions.settings.processing") }}',
                enabled: '{{ __("extensions.settings.enabled") }}',
                installed: '{{ __("extensions.settings.installed") }}',
                success: '{{ __("extensions.bulk.success") }}',
                error: '{{ __("extensions.settings.processing_error") }}',
                version: '{{ __("extensions.modal.version") }}',
                author: '{{ __("extensions.modal.author") }}',
                price: '{{ __("extensions.modal.price") }}',
                tags: '{{ __("extensions.modal.tags") }}',
                buyNow: '{{ __("extensions.modal.buy_now") }}',
                viewDetails: '{{ __("extensions.modal.view_details") }}',
                close: '{{ __("extensions.modal.close") }}',
                enable: '{{ __("extensions.settings.enable") }}',
                disable: '{{ __("extensions.settings.disabled") }}',
                install: '{{ __("extensions.settings.install") }}',
                update: '{{ __("extensions.settings.update") }}'
            }
        });
    });
</script>
<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fadeIn 0.3s ease forwards;
    }

    .animate-fade-out {
        animation: fadeIn 0.3s ease forwards reverse;
    }
</style>
@endsection