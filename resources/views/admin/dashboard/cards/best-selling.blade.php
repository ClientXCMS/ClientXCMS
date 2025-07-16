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
<h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400 mb-2">{{ __('admin.dashboard.widgets.best_selling.title') }}</h3>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <div class="flex flex-col gap-y-3 lg:gap-y-5 p-2 md:p-3 bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800 mt-8">
            <div class="inline-flex justify-center items-center">
                <span class="w-2 h-2 inline-block bg-green-500 rounded-full me-2"></span>
                <span class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">{{ $dto->second->name }}</span>
            </div>

            <div class="text-center">
                <h3 class="text-3xl sm:text-4xl lg:text-5xl font-semibold text-gray-800 dark:text-gray-200">
                    {{ $dto->secondCount }}
                </h3>
            </div>

            <dl class="flex justify-center items-center divide-x divide-gray-200 dark:divide-gray-700">
                <dt class="pe-3">
                    <div class="text-center">

                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $month->secondCount }}</span>
                    </div>
                    <span class="block text-sm text-gray-500">{{ $dto->getLastMonthLabel() }}</span>

                </dt>
                <dd class="text-start ps-3">
                    <div class="text-center">

                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $week->secondCount }}</span>
                    </div>
                    <span class="block text-sm text-gray-500">{{ $dto->getLastWeekLabel() }}</span>

                </dd>
            </dl>
        </div>

        <div class="flex flex-col gap-y-3 lg:gap-y-5 p-2 md:p-3 bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800">
            <div class="inline-flex justify-center items-center">
                <span class="w-2 h-2 inline-block bg-green-500 rounded-full me-2"></span>
                <span class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">{{ $dto->first->name }}</span>
            </div>

            <div class="text-center">
                <h3 class="text-3xl sm:text-4xl lg:text-5xl font-semibold text-gray-800 dark:text-gray-200">
                    {{ $dto->firstCount }}
                </h3>
            </div>

            <dl class="flex justify-center items-center divide-x divide-gray-200 dark:divide-gray-700">
                <dt class="pe-3">
                    <div class="text-center">

                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $month->firstCount }}</span>
                    </div>
                    <span class="block text-sm text-gray-500">{{ $dto->getLastMonthLabel() }}</span>

                </dt>

                <dd class="text-start ps-3">
                    <div class="text-center">

                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $week->firstCount }}</span>
                    </div>
                    <span class="block text-sm text-gray-500">{{ $dto->getLastWeekLabel() }}</span>

                </dd>
            </dl>
        </div>
        <!-- End Card -->

        <!-- Card -->
        <div class="flex flex-col gap-y-3 lg:gap-y-5 p-2 md:p-3 bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800 mt-12">
            <div class="inline-flex justify-center items-center">
                <span class="w-2 h-2 inline-block bg-green-500 rounded-full me-2"></span>
                <span class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">{{ $dto->third->name }}</span>
            </div>

            <div class="text-center">
                <h3 class="text-3xl sm:text-4xl lg:text-5xl font-semibold text-gray-800 dark:text-gray-200">
                    {{ $dto->thirdCount }}
                </h3>
            </div>

            <dl class="flex justify-center items-center divide-x divide-gray-200 dark:divide-gray-700">

                <dt class="pe-3">
                    <div class="text-center">

                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 text-center">{{ $month->thirdCount }}</span>
                    </div>
                        <span class="block text-sm text-gray-500">{{ $dto->getLastMonthLabel() }}</span>
                </dt>
                <dd class="text-start ps-3">
                    <div class="text-center">

                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 text-center">{{ $week->thirdCount }}</span>
                    </div>
                    <span class="block text-sm text-gray-500">{{ $dto->getLastWeekLabel() }}</span>
                </dd>
            </dl>
        </div>
    </div>
