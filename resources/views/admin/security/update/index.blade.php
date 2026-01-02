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

@extends('admin/settings/sidebar')
@section('title', __('admin.update.title'))
@section('setting')
<div class="container mx-auto">
    <div class="alert text-yellow-800 bg-yellow-100 dark:text-yellow-200 dark:bg-yellow-800/20 mb-6" role="alert">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="bi bi-exclamation-triangle-fill text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-bold">{{ __('admin.update.beta_warning_title') }}</h3>
                <div class="mt-1 text-xs">
                    {{ __('admin.update.beta_warning_message') }}
                    <a href="https://docs.clientxcms.com/installation/upgrade" target="_blank" class="underline font-semibold hover:text-yellow-900 dark:hover:text-yellow-100">
                        {{ __('admin.update.beta_warning_link') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @if ($publishedVersions)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="card p-6 h-full">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                    {{ __('admin.update.status_title') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    {{ __('admin.update.status_subtitle') }}
                </p>
                @if (app()->isLocal())
                <div class="alert text-yellow-800 bg-yellow-100 dark:text-yellow-200 dark:bg-yellow-800/20 mt-2" role="alert">
                    <div class="flex">
                        <div class="ml-3">
                            <h3 class="text-sm font-bold">{{ __('admin.update.local_environment') }}</h3>
                            <div class="mt-1 text-xs">
                                {{ __('admin.update.local_environment_message') }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @if (version_compare($currentVersion, $publishedVersions->version, '>='))
                <div class="alert text-green-800 bg-green-100 dark:text-green-200 dark:bg-green-800/20 mt-2" role="alert">
                    <div class="flex">
                        <div class="ml-3">
                            <h3 class="text-sm font-bold">{{ __('admin.update.up_to_date') }}</h3>
                            <div class="mt-1 text-xs">
                                {{ __('admin.update.up_to_date_message') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.update.current_version') }}</p>
                    <span class="text-lg font-bold text-gray-800 dark:text-white">v{{ $currentVersion }}</span>
                </div>
                <form method="POST" action="{{ route('admin.update') }}" class="ajax-extension-form">
                    @csrf
                    <button class="w-full btn btn-primary mt-6">
                        {{ __('admin.update.update_version') }}
                    </button>
                </form>
                @else
                <div class="alert bg-primary text-dark dark:text-white mt-2" role="alert">
                    <div class="flex">
                        <div class="ml-3">
                            <h3 class="text-sm font-bold">{{ __('admin.update.update_available') }}</h3>
                            <div class="mt-1 text-xs">
                                {{ __('admin.update.new_version_is', ['version' => $publishedVersions->version]) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.update.current_version') }}</p>
                        <span class="text-lg font-bold text-red-600 dark:text-red-500">v{{ $currentVersion }}</span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.update.available_version') }}</p>
                        <span class="text-lg font-bold text-green-600 dark:text-green-500">{{ $publishedVersions->version }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.update') }}" class="ajax-extension-form">
                    @csrf
                    <button class="w-full btn btn-primary mt-6">
                        <i class="bi bi-download mr-2"></i>
                        {{ __('admin.update.update_to_version', ['version' => $publishedVersions->version]) }}
                    </button>
                </form>
                @endif
            </div>
        </div>
        <div class="lg:col-span-2">
            <div class="card p-6 h-full">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                            {{ __('admin.update.whats_new_in', ['version' => $publishedVersions->version]) }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $publishedVersions->version_name }} &bull; {{ __('admin.update.published_on', ['date' => \Carbon\Carbon::parse($publishedVersions->created_at)->isoFormat('LL')]) }}
                        </p>
                    </div>
                    <a href="{{ $changelogUrl }}" class="btn btn-secondary flex-shrink-0" target="_blank">
                        <i class="bi bi-book"></i>
                        {{ __('admin.update.changelog') }}
                    </a>
                </div>

                <div class="mt-6 space-y-6">
                    @if (!empty($publishedVersions->changelog->added))
                    <div>
                        <h3 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full  mr-2">➕</span>
                            {{ __('admin.update.added') }}
                        </h3>
                        <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400 pl-2">
                            @foreach ($publishedVersions->changelog->added as $item)
                            <li class="ml-5">{{ Str::after($item, '➕ ') }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if (!empty($publishedVersions->changelog->changed))
                    <div>
                        <h3 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full mr-2">🔄</span>
                            {{ __('admin.update.changed') }}
                        </h3>
                        <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400 pl-2">
                            @foreach ($publishedVersions->changelog->changed as $item)
                            <li class="ml-5">{{ Str::after($item, '🔄 ') }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @else
    <div class="card text-center py-12">
        <i class="bi bi-cloud-slash text-4xl text-gray-400"></i>
        <h3 class="mt-4 text-lg font-semibold text-gray-800 dark:text-white">__('admin.update.error_fetching')</h3>
        <p class="mt-1 text-gray-500 dark:text-gray-400">__('admin.update.error_fetching_message')</p>
    </div>
    @endif
</div>
@endsection
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ajaxForms = document.querySelectorAll('.ajax-extension-form');
        ajaxForms.forEach(form => {
            form.addEventListener('submit', function(event) {
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
                        return Promise.reject(new Error('Internal error.'));
                    }
                }).catch(error => {
                    console.error(error);
                    alert('{{ __('
                        extensions.settings.processing_error ') }}');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;
                });
            });
        });
    });
</script>
@endsection