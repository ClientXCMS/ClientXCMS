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

@section('title', __($translatePrefix .'.title'))
@section('scripts')
<script src="{{ Vite::asset('resources/global/js/sort.js') }}" type="module"></script>
@endsection
@section('setting')
    <div class="container mx-auto">

    <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="card">
                        <div class="card-heading">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __($translatePrefix . '.title') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __($translatePrefix. '.subheading') }}
                                </p>
                            </div>

                            <div class="flex gap-2">
                                @if (count($items) > 1)
                                <button type="button" class="btn btn-secondary text-sm" id="saveSortButton">
                                    {{ __('global.save') }}
                                </button>
                                @endif
                                <button type="button" class="btn btn-primary text-sm" id="social-drawer-open" data-hs-overlay="#social-drawer" onclick="openSocialDrawerCreate()">
                                    {{ __('admin.create') }}
                                </button>
                            </div>
                        </div>

                        @if (count($items) == 0)
                            <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                                <p class="text-sm text-gray-800 dark:text-gray-400">
                                    {{ __('global.no_results') }}
                                </p>
                            </div>
                        @else
                            <ul class="w-full" data-button="#saveSortButton" data-url="{{ route('admin.personalization.socials.sort') }}" data-autosave data-handle=".drag-handle" is="sort-list">
                                @foreach($items as $item)
                                    <li class="sortable-item flex items-center justify-between py-3 px-4 border border-gray-200 rounded-lg mb-2 bg-white hover:bg-gray-50 dark:bg-slate-900 dark:border-gray-700 dark:hover:bg-slate-800" id="{{ $item->id }}"
                                        data-social-id="{{ $item->id }}"
                                        data-social-name="{{ $item->name }}"
                                        data-social-url="{{ $item->url }}"
                                        data-social-icon="{{ $item->icon }}">
                                        <div class="flex items-center gap-x-4">
                                            <i class="bi bi-grip-vertical text-gray-400 drag-handle cursor-grab"></i>
                                            <span class="text-sm text-gray-500 dark:text-gray-400 w-8">#{{ $item->id }}</span>
                                            <span class="text-lg"><i class="{{ $item->icon }}"></i></span>
                                            <a href="#" class="text-sm font-medium text-gray-800 dark:text-gray-200 hover:underline" data-hs-overlay="#social-drawer" onclick="openSocialDrawerEdit(this.closest('li'))">{{ $item->name }}</a>
                                        </div>
                                        <div class="flex items-center gap-x-1">
                                            <button type="button" data-hs-overlay="#social-drawer" onclick="openSocialDrawerEdit(this.closest('li'))">
                                                <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                    <i class="bi bi-eye-fill"></i>
                                                    {{ __('global.show') }}
                                                </span>
                                            </button>
                                            <form method="POST" action="{{ route($routePath . '.show', ['social' => $item]) }}" class="inline confirmation-popup">
                                                @method('DELETE')
                                                @csrf
                                                <button>
                                                    <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                        <i class="bi bi-trash"></i>
                                                        {{ __('global.delete') }}
                                                    </span>
                                                </button>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="py-1 px-4 mx-auto">
                            {{ $items->links('admin.shared.layouts.pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden trigger for programmatic open (keeps Preline state in sync) --}}
    <button type="button" id="social-drawer-trigger" data-hs-overlay="#social-drawer" class="hidden"></button>

    {{-- Drawer for create/edit social network --}}
    <div id="social-drawer" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 id="social-drawer-title" class="font-bold text-gray-800 dark:text-white">
                {{ __($translatePrefix . '.create.title') }}
            </h3>
            <button type="button" class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#social-drawer">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="p-4">
            <form id="social-drawer-form" method="POST" action="{{ route($routePath . '.store') }}">
                @csrf
                <input type="hidden" id="social-drawer-method" name="_method" value="POST" disabled>

                <div class="space-y-4">
                    <div>
                        <label for="social-input-name" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ __('global.name') }}</label>
                        <div class="mt-2">
                            <input type="text" name="name" id="social-input-name" value="{{ old('name') }}" class="input-text @error('name') border-red-500 @enderror">
                            @error('name')
                            <span class="mt-2 text-sm text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="social-input-url" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ __('global.url') }}</label>
                        <div class="mt-2">
                            <input type="text" name="url" id="social-input-url" value="{{ old('url') }}" class="input-text @error('url') border-red-500 @enderror">
                            @error('url')
                            <span class="mt-2 text-sm text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="social-input-icon" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">
                            {{ __('personalization.icon') }}
                            <a href="https://icons.getbootstrap.com" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                <i class="bi bi-info-circle-fill"></i>
                                <span class="text-xs underline">{{ __('personalization.icon_help') }}</span>
                            </a>
                        </label>
                        <div class="mt-2">
                            <input type="text" name="icon" id="social-input-icon" value="{{ old('icon') }}" class="input-text @error('icon') border-red-500 @enderror">
                            @error('icon')
                            <span class="mt-2 text-sm text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <button id="social-drawer-submit" type="submit" class="btn btn-primary mt-4 w-full">
                    {{ __('admin.create') }}
                </button>
            </form>
        </div>
    </div>

    <script>
        function clearDrawerErrors() {
            document.querySelectorAll('#social-drawer-form .text-red-500').forEach(el => el.remove());
            document.querySelectorAll('#social-drawer-form .border-red-500').forEach(el => el.classList.remove('border-red-500'));
        }

        function openSocialDrawerCreate() {
            clearDrawerErrors();

            const form = document.getElementById('social-drawer-form');
            const methodInput = document.getElementById('social-drawer-method');
            const title = document.getElementById('social-drawer-title');
            const submitBtn = document.getElementById('social-drawer-submit');

            form.action = @json(route($routePath . '.store'));
            methodInput.disabled = true;

            title.textContent = @json(__($translatePrefix . '.create.title'));
            submitBtn.textContent = @json(__('admin.create'));

            document.getElementById('social-input-name').value = '';
            document.getElementById('social-input-url').value = '';
            document.getElementById('social-input-icon').value = '';
        }

        function openSocialDrawerEdit(li) {
            clearDrawerErrors();

            const form = document.getElementById('social-drawer-form');
            const methodInput = document.getElementById('social-drawer-method');
            const title = document.getElementById('social-drawer-title');
            const submitBtn = document.getElementById('social-drawer-submit');

            const id = li.dataset.socialId;
            const updateUrl = @json(route($routePath . '.update', ['social' => '__ID__']));
            form.action = updateUrl.replace('__ID__', id);
            methodInput.value = 'PUT';
            methodInput.disabled = false;

            title.textContent = @json(__($translatePrefix . '.show.title', ['name' => '__NAME__']));
            title.textContent = title.textContent.replace('__NAME__', li.dataset.socialName);
            submitBtn.textContent = @json(__('admin.updatedetails'));

            document.getElementById('social-input-name').value = li.dataset.socialName;
            document.getElementById('social-input-url').value = li.dataset.socialUrl;
            document.getElementById('social-input-icon').value = li.dataset.socialIcon;
        }

        {{-- Auto-open drawer if validation errors came back from store/update --}}
        @if ($errors->any())
            window.addEventListener('load', function() {
                document.getElementById('social-drawer-trigger').click();
            });
        @endif
    </script>
@endsection
