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
@section('title',  __($translatePrefix . '.show.title', ['name' => $item->username]))
@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.querySelectorAll('[id^="selectAll_"]');
            selectAll.forEach((element) => {
                element.addEventListener('change', function () {
                    const checkboxes = this.parentNode.parentNode.parentNode.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = this.checked;
                    });
                });
            });
        });
    </script>
@endsection
@section('content')
    <div class="container mx-auto">

    @include('admin/shared/alerts')

            <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex flex-col">
                        <div class="-m-1.5 overflow-x-auto">
                            <div class="p-1.5 min-w-full inline-block align-middle">
                                <form class="card" method="POST" action="{{ route($routePath . '.update', ['role' => $item]) }}">
                                <div class="card-heading">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $item->id }}">
                                        @method('PUT')
                                        <div>
                                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                                {{ __($translatePrefix . '.show.title', ['name' => $item->name]) }}
                                            </h2>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ __($translatePrefix. '.show.subheading', ['date' => $item->created_at != null ?  $item->created_at->format('d/m/y') : 'None']) }}
                                            </p>
                                        </div>
                                        <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                                            <button class="btn btn-primary">
                                                {{ __('admin.updatedetails') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => old('name', $item->name)])
                                            <div class="mt-2">
                                                @include('admin/shared/checkbox', ['name' => 'is_default', 'label' => __('admin.roles.is_default'), 'value' => 'true', 'checked' => old('is_default', $item->is_default)])
                                            </div>
                                        </div>

                                        <div class="col-span-2">
                                            @include('admin/shared/input', ['name' => 'level', 'label' => __($translatePrefix . '.level'), 'value' => old('level', $item->level), 'type' => 'number', 'help' => __('admin.roles.levelhelp')])
                                        </div>
                                        <div class="content-center mt-6">
                                            @include('admin/shared/checkbox', ['name' => 'is_admin', 'label' => __('admin.roles.is_admin'), 'value' => 'true', 'checked' => old('is_admin', $item->is_admin)])
                                            <p class="text-sm text-gray-500 mt-2">{{ __('admin.roles.admin_help') }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                                        @foreach($permissions as $label => $row)
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">

                                                    <div class="flex items-center">
                                                        <input type="checkbox" value="{{ $value ?? 'true' }}"
                                                               class="shrink-0 mt-1 border-gray-200 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
                                                               id="selectAll_{{ $label }}">
                                                            <label for="selectAll_{{ $label }}" class="font-semibold text-gray-800 dark:text-gray-200 ms-3 mt-1">{{ __($label) }}</label>
                                                    </div>
                                                    </h3>
                                                    @foreach($row as $permission)
                                                        <div class="mb-1">
                                                            @include('admin/shared/checkbox', ['name' => 'permissions[]', 'label' => $permission->translate(), 'value' => $permission->id, 'checked' => in_array($permission->id, $item->permissions->pluck('id')->toArray())])
                                                        </div>
                                                    @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
        @if (staff_has_permission('admin.manage_staff'))
            @php($items = $item->staffs)
    <div class="card">
        <div class="card-heading">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                {{ __('admin.admins.title') }}
            </h2>
        </div>
        <div class="card-body">
            <div class="overflow-x-auto">

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
                                            {{ __('global.name') }}
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('global.email') }}
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
                      {{ __('admin.customers.show.last_login') }}
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
                                <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                        <p class="text-sm text-gray-800 dark:text-gray-400">
                                            {{ __('global.no_results') }}
                                        </p>
                                    </div>
                                </td>
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
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->username }}</span>
                    </span>
                                </td>
                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->email }}</span>
                    </span>
                                </td>
                                <td class="h-px w-px whitespace-nowrap">
                                    @if ($item->isActive())

                                        <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
          <div class="hs-tooltip">
            <div class="hs-tooltip-toggle">
                              @if ($item->expires_at != null)
                    <svg class="flex-shrink-0 w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                    <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-slate-700" role="tooltip">
                      {{ __('admin.staffs.badge.expires_at', ['date' => \Carbon\Carbon::parse($item->expires_at)->format('d/m/y')]) }}
              </span>
                @else
                    <i class="bi bi-person-badge flex-shrink-0 w-4 h-4 text-gray-500"></i>
                @endif
            </div>
          </div>
                            {{ __('global.active') }}

</span>
                                    @else

                                        <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
          <div class="hs-tooltip">
            <div class="hs-tooltip-toggle">
                              @if ($item->expires_at != null)

                    <svg class="flex-shrink-0 w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                    <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-slate-700" role="tooltip">
                                            {{ __($translatePrefix . '.badge.expired_at', ['date' => \Carbon\Carbon::parse($item->expires_at)->format('d/m/y')]) }}

              </span>

                @else
                    <i class="bi bi-person-badge flex-shrink-0 w-4 h-4 text-gray-500"></i>
                @endif
            </div>
          </div>
                            {{ __('global.inactive') }}

</span>
                                    @endif
                                </td>


                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->last_login != null ? $item->last_login->format('d/m/y H:i:s') : 'None' }}</span>
                    </span>
                                </td>
                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->created_at != null ? $item->created_at->format('d/m/y') : 'None' }}</span>
                    </span>
                                </td>
                                <td class="h-px w-px whitespace-nowrap">

                                    <a href="{{ route('admin.staffs.show', ['staff' => $item]) }}">
                                        <span class="py-1.5">
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                              <i class="bi bi-eye-fill"></i>
                                            {{ __('global.show') }}
                                          </span>
                                        </span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
            </div>
        </div>
    @endif

@endsection
