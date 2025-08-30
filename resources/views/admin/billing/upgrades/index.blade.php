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

@extends('admin/layouts/admin')
@section('title', __($translatePrefix .'.title'))
@section('content')
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
                                    {{ __($translatePrefix. '.description') }}
                                </p>
                            </div>

                            @include('admin/shared/mass_actions/header', ['searchFields' => $searchFields, 'search' => $search, 'searchField' => $searchField, 'filters' => $filters, 'checkedFilters' => $checkedFilters])
                        </div>
                    </div>
                    <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
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
                      {{ __('global.customer') }}
                    </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.service') }}
                    </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('provisioning.admin.upgrade_services.old_product') }}
                    </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('provisioning.admin.upgrade_services.new_product') }}
                    </span>
                                    </div>
                                </th>


                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                      {{ __('global.invoice') }}
                                    </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('provisioning.admin.upgrade_services.upgraded') }}
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
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                          <a href="{{ route('admin.customers.show', ['customer' => $item->customer]) }}">
                          {{ $item->customer->excerptFullName() }}</span>
                        </a>
                    </span>
                                    </td>

                                    <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                          <a href="{{ route('admin.services.show', ['service' => $item->service]) }}">
                          {{ $item->service->name }}</span>
                        </a>
                    </span>
                                    </td>

                                    <td class="h-px w-px whitespace-nowrap">
                                                <span class="block px-6 py-2">
                                                    <a href="{{ route('admin.products.show', ['product' => $item->oldProduct]) }}">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->oldProduct->trans('name') }}</span>
                                                    </a>
                                                </span>
                                    </td>

                                    <td class="h-px w-px whitespace-nowrap">
                                                <span class="block px-6 py-2">
                                                    <a href="{{ route('admin.products.show', ['product' => $item->newProduct]) }}">
                                                  <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->newProduct->trans('name') }}</span>
                                                    </a>
                                                </span>
                                    </td>

                                    <td class="h-px w-px whitespace-nowrap">
                                        @if ($item->invoice == null)
                                            <span class="block px-6 py-2">
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('global.none') }}</span>
                                                    </span>
                                        @else
                                            <span class="block px-6 py-2">
                                            <a href="{{ route('admin.invoices.show', ['invoice' => $item->invoice]) }}">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->invoice->identifier() }}</span>
                                            </a>
                                                </span>
                                        @endif
                                    </td>

                                    <td class="h-px w-px px-6 whitespace-nowrap">

                                        @if ($item->upgraded)
                                            <span class="mx-auto py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
  <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
  {{ __('global.yes') }}
</span>
                                        @else
                                            <span class="mx-auto py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
  <svg class="flex-shrink-0 w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
  {{ __('global.no') }}
</span>
                                        @endif</td>
                                    <td class="h-px w-px whitespace-nowrap">
                                                <span class="block px-6 py-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->created_at->format('d/m/y H:i') }}</span>
                                                </span>
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
    </div>
@endsection
