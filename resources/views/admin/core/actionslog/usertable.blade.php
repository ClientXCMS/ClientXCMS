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

<div class="border rounded-lg overflow-hidden dark:border-gray-700">

<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
    <thead>
    <tr>
        <th scope="col" class="px-6 py-3 text-start">
            <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('auth.login.title') }}
                    </span>
            </div>
        </th>

        <th scope="col" class="px-6 py-3 text-start">
            <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.date') }}
                    </span>
            </div>
        </th>
    </tr>
    </thead>

    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
    @if (count($logs) == 0)
        <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center">
                <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                    <p class="text-sm text-gray-800 dark:text-gray-400">
                        {{ __('global.no_results') }}
                    </p>
                </div>
            </td>
    @endif
    @foreach($logs as $log)

        <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

            <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            <i class="{{ $log->getIcon() }}"></i>
                            {{ $log->getFormattedName() }}
                        </span>
                    </span>
            </td>

            <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $log->created_at != null ? $log->created_at->format('d/m/y H:i:s') : 'None' }}</span>
                    </span>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>

<div class="py-1 px-4 mx-auto">
    {{ $logs->links('admin.shared.layouts.pagination') }}
</div>
