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

@extends('admin.layouts.admin')
@section('content')
    <div class="container mx-auto">

        @include('admin.shared.alerts')

        <div class="grid grid-cols-6 gap-4">

            <div class="col-span-6 md:col-span-1">
                <div class="card">
                    <div class="">

                        <nav class="hs-accordion-group w-full flex flex-col flex-wrap">
                            <ul>
                                @foreach (app('settings')->getCards() as $card)
                                    <li class="hs-accordion" id="{{ $card->uuid }}-accordion">
                                        <button type="button" class="flex hs-accordion-toggle text-left w-full py-4 hs-accordion-active:text-blue-600 hs-accordion-active:hover:bg-transparent text-sm text-slate-700 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-900 dark:text-slate-400 dark:hover:text-slate-300 dark:hs-accordion-active:text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                            {{ __($card->name) }}
                                            <svg class="hs-accordion-active:block ms-auto hidden w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m18 15-6-6-6 6"/>
                                            </svg>
                                            <svg class="hs-accordion-active:hidden ms-auto w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m6 9 6 6 6-6"/>
                                            </svg>
                                        </button>

                                        <div id="{{ $card->uuid }}-accordion-child" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300 {{ $card->uuid != $current_card->uuid  ?? null ? 'hidden' : '' }}">
                                            <ul class="hs-accordion-group ps-3 pt-2">
                                                @foreach($card->items as $child)

                                                    <li>
                                                        <a {{ !$child->isActive() ? 'disabled="true"' : '' }} class="{{ !$child->isActive() ? 'cursor-not-allowed' : '' }} flex items-center py-4 text-sm {{ $child->uuid == isset($current_item) && $current_item->uuid ? 'text-primary' : 'text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }} rounded-lg hover:bg-gray-100 dark:bg-gray-800   dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="{{ !$child->isActive() ? '#':  $child->url() }}">
                                                            {{ __($child->name) }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="col-span-6 md:col-span-5" id="setting">
                @yield('setting')
            </div>
        </div>
    </div>
        @yield('script')
        <script>
            let checkboxes = document.querySelectorAll('#setting input[type=checkbox]');

            checkboxes.forEach(function(checkbox) {
                checkbox.value = checkbox.checked ? "true" : "false";
                checkbox.addEventListener('change', function() {
                    checkbox.value = checkbox.checked ? "true" : "false";
                });
            });
        </script>
@endsection
