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
@section('title', __($translatePrefix . '.title'))

@section('setting')
<div class="card">
    <div class="card-heading">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                {{ __($translatePrefix . '.title') }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __($translatePrefix . '.description') }}
            </p>
        </div>
        <div class="flex">
            <form>
                <label for="hs-as-table-product-review-search" class="sr-only">{{ __('global.search') }}</label>
                <div class="relative">
                    <input type="text" value="{{ $search ?? '' }}" id="hs-as-table-product-review-search" name="q" class="py-2 px-3 ps-11 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600" placeholder="{{ __('global.search') }}">
                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-4">
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                        </svg>
                    </div>
                </div>
            </form>
            <a class="btn btn-primary text-sm ml-2" href="{{ route($routePath . '.create') }}">
                {{ __('admin.create') }}
            </a>
        </div>
    </div>

    <div class="border rounded-lg overflow-hidden dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr>
                    <th scope="col" class="px-6 py-3 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">#</span>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __($translatePrefix . '.fields.question') }}</span>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.status') }}</span>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('global.actions') }}</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @if (count($items) == 0)
                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                            <i class="bi bi-question-circle text-4xl text-gray-300 dark:text-gray-600 mb-2"></i>
                            <p class="text-sm text-gray-800 dark:text-gray-400">
                                {{ __($translatePrefix . '.empty') }}
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
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ \Str::limit($item->question, 50) }}</span>
                        </span>
                    </td>
                    <td class="h-px w-px whitespace-nowrap">
                        @if ($item->is_active)
                        <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                            {{ __('global.states.active') }}
                        </span>
                        @else
                        <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full dark:bg-gray-500/10 dark:text-gray-500">
                            {{ __('global.states.inactive') }}
                        </span>
                        @endif
                    </td>
                    <td class="h-px w-px whitespace-nowrap">
                        <a href="{{ route($routePath . '.show', $item) }}">
                            <span class="py-1.5">
                                <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                    <i class="bi bi-eye-fill"></i>
                                    {{ __('global.show') }}
                                </span>
                            </span>
                        </a>
                        <form method="POST" action="{{ route($routePath . '.destroy', $item) }}" class="inline confirmation-popup">
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
@endsection