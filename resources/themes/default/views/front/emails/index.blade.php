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

@extends('layouts/client')
@section('scripts')
    <script src="{{ Vite::asset('resources/themes/default/js/popupwindow.js') }}" type="module" defer></script>
@endsection
@section('title', __('client.emails.index'))
@section('content')
    @include("shared.alerts")
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">

            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="card">
                            <div class="card-heading">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                        {{ __('client.emails.index') }}
                                    </h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('client.emails.index_description') }}
                                    </p>
                                </div>
                                <div>
                                    <form class="relative max-w-xs">
                                        <label class="sr-only">{{ __('global.search') }}</label>
                                        <input type="text" name="search" value="{{ $search ?? '' }}" id="hs-table-with-pagination-search" class="py-2 px-3 ps-9 block w-full border-gray-200 shadow-sm rounded-lg text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600" placeholder="{{ __('global.search') }}">
                                        <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3">
                                            <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start">
                                            <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('client.emails.subject') }}
                    </span>
                                            </div>
                                        </th>

                                        <th scope="col" class="px-6 py-3">
                                            <div class="whitespace-nowrap text-right text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.date') }}
                    </span>
                                            </div>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-end"></th>
                                    </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @if (count($emails) == 0)
                                        <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center">
                                                <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                                    <p class="text-sm text-gray-800 dark:text-gray-400">
                                                        {{ __('global.no_results') }}
                                                    </p>
                                                </div>
                                            </td>
                                    @endif
                                    @foreach($emails as $email)
                                        <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                            <td class="h-px w-px whitespace-nowrap">
                                                <div class="block px-6 py-2">
                                                <a href="{{ route('front.emails.show', $email->id) }}" is="popup-window" class="inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-blue-600 hover:text-blue-800 dark:text-blue-500 dark:hover:text-blue-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                                    {{ $email->subject }}
                                                </a>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $email->created_at->format('d/m/y H:i') }}
                                                </div>
                                            </td>
                                            <td class="h-px w-px whitespace-nowrap">
                                                <a href="{{ route('front.emails.show', ['email' => $email->id]) }}" class="block"  is="popup-window" >
                                                    <span class="px-6 py-1.5">
                                                      <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                          <i class="bi bi-eye-fill"></i>
                                                        {{ __('global.view') }}
                                                      </span>
                                                    </span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="py-1 px-4 mx-auto">
                                {{ $emails->links('shared.layouts.pagination') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    <!-- End Card Section -->
@endsection
