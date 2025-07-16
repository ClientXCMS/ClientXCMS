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
@extends('admin/layouts/admin')
@section('title', __($translatePrefix .'.title'))
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/admin/filter.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container mx-auto">

    @include('admin/shared/alerts')
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

                            @include('admin/shared/mass_actions/header', ['searchFields' => $searchFields, 'search' => $search, 'searchField' => $searchField, 'filters' => $filters, 'checkedFilters' => $checkedFilters])
                            @if (staff_has_permission('admin.manage_personalization'))
                                <a class="btn btn-primary text-sm sm:ml-1 mt-2 sm:mt-0 w-full max-w-md sm:w-auto" href="{{ route($routePath . '.create') }}">
                                    {{ __('admin.create') }}
                                </a>
                                <a class="btn btn-secondary text-sm sm:ml-1 mt-2 sm:mt-0 w-full max-w-md sm:w-auto" href="#" data-hs-overlay="#import-overlay">
                                    {{ __($translatePrefix . '.import.btn') }}
                                </a>
                            @endif

                        </div>
                    </div>
                    <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="mass_action_table">
                            <thead>

                            <tr>

                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      #
                    </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.name') }}
                    </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('global.locale') }}
                    </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.status') }}
                    </span>
                                    </div>
                                </th>


                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.created') }}
                    </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">

                                        {{ __('global.actions') }}
                                                            </span>
                                </th>
                            </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @if (count($items) == 0)
                                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                    <td colspan="8" class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                            <p class="text-sm text-gray-800 dark:text-gray-400">
                                                {{ __('global.no_results') }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                            @foreach($items as $item)

                                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

                                    <td class="h-px w-px whitespace-nowrap">
                                        <span class="block px-6 py-2">
                                          <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->id }}</span>
                                        </span>
                                    </td>
                                    <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $translations[$item->name] ?? $item->name }}</span>
                    </span>
                                    </td>
                                    <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $locales[$item->locale] ?? $item->locale }}</span>
                    </span>
                                    </td>

                                    <td class="h-px w-px whitespace-nowrap px-6">
                                        <x-badge-state state="{{ $item->hidden == 1 ? 'hidden' : 'active' }}"></x-badge-state>
                                    </td>

                                    <td class="h-px w-px whitespace-nowrap">
                                                        <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->created_at->format('d/m/y') }}</span>
                    </span>
                                    </td>
                                    <td class="h-px w-px whitespace-nowrap">

                                        <a href="{{ route($routePath . '.show', ['email_template' => $item]) }}">
                                        <span class="py-1.5">
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                              <i class="bi bi-eye-fill"></i>
                                            {{ __('global.show') }}
                                          </span>
                                        </span>
                                        </a>

                                            <form method="POST" action="{{ route($routePath . '.show', ['email_template' => $item]) }}" class="inline confirmation-popup">
                                                @method('DELETE')
                                                @csrf
                                                <button>
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                              <i class="bi bi-trash"></i>
                                            {{ __('global.delete') }}
                                          </span>
                                                </button>
                                            </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="py-1 px-4 mx-auto">
                        {{ $items->links('admin.shared.layouts.pagination') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="import-overlay" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                {{ __($translatePrefix . '.import.title') }}
            </h3>
            <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#config-overlay">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="p-4">
            <form method="POST" action="{{ route($routePath . '.import') }}" enctype="multipart/form-data">
                @include('admin/shared/file', ['name' => 'file', 'canRemove' => false, 'extensions' => 'blade.php'])
                @if (in_array(strtolower(setting('email_template_name')), ['wave.blade', 'welcome.blade']))
                    @include('admin/shared/input', ['name' => 'email_template_title', 'label' => __('global.name'), 'value' => old('email_template_title', setting('email_template_title'))])
                    @include('admin/shared/input', ['name' => 'email_template_description', 'label' => __('global.description'), 'value' => old('email_template_description', setting('email_template_description'))])
                    @include('admin/shared/input', ['name' => 'email_template_image', 'label' => 'Image', 'value' => old('email_template_image', setting('email_template_image', "https://clientxcms.com/Themes/CLIENTXCMS/images/emails/support3.png"))])
                @endif
                @if (in_array(strtolower(setting('email_template_name')), ['welcome.blade']))
                    @include('admin/shared/input', ['name' => 'email_template_image2', 'label' => 'Image 2', 'value' => old('email_template_image2', setting('email_template_image2', 'https://clientxcms.com/Themes/CLIENTXCMS/images/emails/node.png'))])
                @endif
                @csrf
                <button class="btn btn-primary mt-2 w-full">{{ __('global.save') }}</button>

            </form>
        </div>
    </div>
@endsection
