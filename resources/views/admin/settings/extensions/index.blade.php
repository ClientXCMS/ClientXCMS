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

@section('setting')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 card">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">@lang('extensions.settings.title')</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">@lang('extensions.settings.description')</p>
            </div>
            <form action="{{ route('admin.settings.extensions.clear') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-warning flex-shrink-0">
                    <i class="bi bi-arrow-clockwise"></i> @lang('extensions.settings.clearcache')
                </button>
            </form>
        </div>
        <div class="mb-4">
            <label for="extension-search-input" class="sr-only"> @lang('extensions.settings.search')</label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <i class="bi bi-search text-gray-400"></i>
                </div>
                <input type="text"
                       id="extension-search-input"
                       class="py-2 px-3 ps-10 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-slate-400 dark:focus:ring-slate-600"
                       placeholder=" @lang('extensions.settings.search_placeholder')">
            </div>
        </div>

        @if (!empty($groups))
            <div>
                    <nav class="flex space-x-2 border-b flex-col md:flex-row border-gray-200 dark:border-slate-700" aria-label="Tabs" role="tablist">
                        @foreach ($groups as $groupName => $extensions)
                            @php $slug = Str::slug($groupName); @endphp
                            <button type="button" class="hs-tab-active:border-primary hs-tab-active:text-primary -mb-px py-3 px-4 inline-flex items-center gap-x-2 border-b-2 border-transparent text-sm font-medium text-center text-gray-500 hover:text-primary disabled:opacity-50 disabled:pointer-events-none dark:text-slate-400 dark:hover:text-white {{ $loop->first ? 'active' : '' }}" id="tab-{{ $slug }}" data-hs-tab="#tab-content-{{ $slug }}" aria-controls="tab-content-{{ $slug }}" role="tab">
                                {{ $groupName }}
                            </button>
                        @endforeach
                    </nav>
                <div class="mt-6">
                    @foreach ($groups as $groupName => $extensions)
                        @php $slug = Str::slug($groupName); @endphp
                        <div id="tab-content-{{ $slug }}" role="tabpanel" class="{{ $loop->first ? '' : 'hidden' }}" aria-labelledby="tab-{{ $slug }}">
                            <div class="grid gap-6">
                                @foreach ($extensions as $extension)

                                            <div class="group extension-card bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700 flex flex-col md:flex-row overflow-hidden transition-shadow hover:shadow-md">

                                                <div class="md:w-52 flex-shrink-0 flex items-center justify-center bg-gray-100 dark:bg-slate-800 {{ $extension->hasPadding() ? 'p-4' :' ' }} rounded-t-xl md:rounded-l-xl md:rounded-t-none">
                                                    @if ($extension->thumbnail())
                                                        <img src="{{ $extension->thumbnail() }}" class="max-w-full h-32 md:h-full object-contain" alt="{{ $extension->name() }}">
                                                    @endif
                                                </div>

                                                <div class="p-4 md:p-6 flex flex-col flex-grow">
                                                    <div class="flex justify-between items-start">
                                                        <span class="block text-xs font-semibold uppercase text-primary dark:text-blue-500">
                                                          {{ str_replace('_', ' ', $extension->type) }}
                                                        </span>
                                                        <div class="flex items-end gap-2">
                                                            @if($extension->isEnabled())
                                                                <span class="inline-flex items-center gap-x-1.5 py-1 px-2 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500">{{ __('extensions.settings.enabled') }}</span>
                                                            @elseif($extension->isInstalled())
                                                                <span class="inline-flex items-center gap-x-1.5 py-1 px-2 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-500">{{ __('extensions.settings.installed') }}</span>
                                                            @endif

                                                            @if ($extension->isInstalled())
                                                                @if ($extension->getLatestVersion() && version_compare($extension->version, $extension->getLatestVersion(), '<'))
                                                                    <span class="inline-flex items-center gap-x-1.5 py-1 px-2 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500" title="Mise à jour disponible">
                                                                        <i class="bi bi-arrow-up-circle"></i>
                                                                        {{ $extension->version }} &rarr; {{ $extension->getLatestVersion() }}
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center gap-x-1.5 py-1 px-2 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-slate-300">
                                                                        {{ $extension->version }}
                                                                    </span>
                                                                @endif
                                                            @endif
                                                            <span class="inline-flex items-center gap-x-1.5 py-1 px-2 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-slate-300">
                                                                {{ $extension->price(true) }}
                                                        </div>
                                                    </div>

                                                    <h3 class="text-xl font-semibold text-gray-800 dark:text-slate-300 -mt-2">
                                                        {{ $extension->name() }}
                                                    </h3>

                                                    <div class="flex justify-between items-center mt-2">
                                                        @if(isset($extension->api['reviews_count']) && $extension->api['reviews_count'] > 0)
                                                            <div class="flex items-center">
                                                                <div class="flex text-yellow-500">
                                                                    @for ($i = 1; $i <= 5; $i++)
                                                                        <i class="bi {{ $i <= round($extension->api['reviews_avg_rating'] ?? 0) ? 'bi-star-fill' : 'bi-star' }}"></i>
                                                                    @endfor
                                                                </div>
                                                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">({{ $extension->api['reviews_count'] }} {{ __('extensions.settings.reviews') }})</span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <p class="mt-3 text-gray-500 dark:text-slate-400 text-sm flex-grow">
                                                        {{ $extension->description() }}
                                                    </p>

                                                    @if(!empty($extension->api['tags']))
                                                        <div class="mt-4">
                                                            @foreach(array_slice($extension->api['tags'], 0, 3) as $tag)
                                                                <span class="inline-block bg-gray-200 dark:bg-slate-700 rounded-full px-3 py-1 text-xs font-semibold text-gray-700 dark:text-slate-300 mr-2 mb-2">
                                @if(!empty($tag['icon'])) <i class="{{ $tag['icon'] }} mr-1"></i> @endif
                                                                    {{ $tag['name'] }}
                            </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    <span class="mt-2 inline-flex items-center gap-x-1.5 text-xs font-medium text-gray-600 dark:text-slate-400">
                                                        <i class="bi bi-person-fill"></i>
                                                        {{ $extension->author() }}
                                                    </span>

                                                </div>
                                                <div class="md:w-56 w-46 flex-shrink-0 flex flex-col justify-center items-center space-y-2 p-4 bg-gray-50 dark:bg-slate-800 rounded-b-xl md:rounded-r-xl md:rounded-b-none">
                                                    @if ($extension->isInstalled() && $extension->getLatestVersion() && version_compare($extension->version, $extension->getLatestVersion(), '<'))
                                                        <form action="{{ route('admin.settings.extensions.update', [$extension->type(), $extension->uuid]) }}" method="POST" class="w-full ajax-extension-form"> @csrf

                                                        <button class="w-full btn btn-info"><i class="bi bi-download"></i> {{ __('extensions.settings.update') }}</button>
                                                        </form>
                                                    @elseif ($extension->isEnabled() && $extension->isActivable())
                                                        <form action="{{ route('admin.settings.extensions.disable', [$extension->type(), $extension->uuid]) }}" method="POST" class="w-full"> @csrf
                                                            <button type="submit" class="w-full btn btn-danger"><i class="bi bi-ban"></i>
                                                                {{ __('extensions.settings.disabled') }}</button>
                                                        </form>
                                                    @elseif ($extension->isInstalled() && !$extension->isEnabled() && $extension->isActivable())
                                                        <form action="{{ route('admin.settings.extensions.enable', [$extension->type(), $extension->uuid]) }}" method="POST" class="w-full"> @csrf
                                                            <button type="submit" class="w-full btn btn-success"><i class="bi bi-check-circle"></i>
                                                                {{ __('extensions.settings.enable') }}</button>
                                                        </form>
                                                    @elseif ($extension->isNotInstalled() && $extension->isActivable())
                                                        <form action="{{ route('admin.settings.extensions.update', [$extension->type(), $extension->uuid]) }}" method="POST" class="w-full ajax-extension-form"> @csrf

                                                        <button class="w-full btn btn-primary"><i class="bi bi-cloud-download"></i>
                                                            {{ __('extensions.settings.install') }}</button>
                                                        </form>
                                                    @else
                                                        <a class="w-full btn btn-primary" href="{{ $extension->api['route'] }}" target="_blank"><i class="bi bi-cart"></i>
                                                            {{ __('extensions.settings.buy') }}</a>
                                                    @endif
                                                    @if (isset($extension->api['route']))
                                                    <a class="w-full btn btn-secondary" href="{{ $extension->api['route'] }}" target="_blank">
                                                        <i class="bi bi-box-arrow-up-right"></i> {{ __('global.details') }}
                                                    </a>
                                                        @endif
                                                </div>

                                            </div>
                                @endforeach
                                    </div>
                            </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-12 bg-white dark:bg-slate-800 rounded-lg shadow-md">
                <i class="bi bi-folder-x text-4xl text-gray-400"></i>
                <p class="mt-4 text-gray-500 dark:text-gray-400">{{ __('extensions.settings.no_extensions_found') }}</p>
            </div>
        @endif
    </div>
@endsection
@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('extension-search-input');
            const allExtensionCards = document.querySelectorAll('.extension-card');
            const tabPanels = document.querySelectorAll('[role="tabpanel"]');
            const ajaxForms = document.querySelectorAll('.ajax-extension-form');

            searchInput.addEventListener('input', function () {
                const searchTerm = searchInput.value.toLowerCase().trim();
                allExtensionCards.forEach(card => {
                    const cardContent = card.textContent || card.innerText;
                    const isVisible = cardContent.toLowerCase().includes(searchTerm);
                    card.style.display = isVisible ? '' : 'none';
                });
                updateNoResultsMessages();
            });
            function updateNoResultsMessages() {
                tabPanels.forEach(panel => {
                    const noResultsMessage = panel.querySelector('.no-results-message');
                    const visibleCardsInPanel = panel.querySelectorAll('.extension-card:not([style*="display: none"])');
                    if (visibleCardsInPanel.length === 0) {
                        noResultsMessage.classList.remove('hidden');
                    } else {
                        noResultsMessage.classList.add('hidden');
                    }
                });
            }
            ajaxForms.forEach(form => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    const submitButton = form.querySelector('button');
                    const originalButtonContent = submitButton.innerHTML;
                    submitButton.disabled = true;
                    submitButton.innerHTML = `
                <span class="inline-flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('extensions.settings.processing') }}
                </span>
            `;
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form)
                    }).then(response => {
                        if (response.ok) {
                            window.location.reload();
                        } else {
                            return response.json().then((json) => {
                                return Promise.reject(json.error || 'An error occurred');
                            })
                        }
                    }).catch(error => {
                        alert(error);
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonContent;
                    });
                });
            });
        });
    </script>
@endsection
