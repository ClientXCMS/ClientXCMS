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

<div class="card">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3">

        {{ __($translatePrefix . '.show.payment-methods') }}</h2>
    <div class="border rounded-lg overflow-hidden dark:border-gray-700">

        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
            <tr>

                <th scope="col" class="px-6 py-3 text-start">
                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                        {{ __('client.payment-methods.card') }}
                    </span>
                    </div>
                </th>
                <th scope="col" class="px-6 py-3 text-start">
                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('client.payment-methods.default') }}
                    </span>
                    </div>
                </th>

                <th scope="col" class="px-6 py-3 text-start">
                    <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.actions') }}
                    </span>
                    </div>
                </th>
            </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @if (count($paymentmethods) == 0)
                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                            <p class="text-sm text-gray-800 dark:text-gray-400">
                                {{ __('global.no_results') }}
                            </p>
                        </div>
                    </td>
            @endif
            @foreach($paymentmethods as $source)
                <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                    @if ($source->gateway_uuid == 'paypal_express_checkout')
                        <td class="h-px w-px whitespace-nowrap">
            <span class="block px-6 py-2">
                <span class="block text-sm font-medium text-gray-800 dark:text-neutral-200">
                    <i class="bi bi-paypal"></i> {{ $source->email }}
                </span>
            </span>
                        </td>
                    @else
                        <td class="h-px w-px whitespace-nowrap">
            <span class="block px-6 py-2">
                <span class="block text-sm font-medium text-gray-800 dark:text-neutral-200">
                    •••• {{ $source->last4 ?? $source->email }}
                </span>
            </span>
                        </td>
                    @endif

                    <td class="h-px w-px whitespace-nowrap">
                        <x-badge-state state="{{ $source->isDefault($item) ? 'yes' : 'no' }}"></x-badge-state>
                    </td>


                    <td class="h-px w-px whitespace-nowrap">
                        <div class="flex">
                            @if (!$source->isDefault($item))
                                <form action="{{ route('front.payment-methods.default', ['paymentMethod' => $source->id]) }}?customer_id={{ $item->id }}" method="POST">
                                    @csrf
                                    <button>
                                          <span class="py-1 px-2 inline-flex mr-2 justify-center items-center gap-2 rounded-lg border font-medium bg-slate text-slate-700 shadow-sm align-middle hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-slate-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                <i class="bi bi-sliders2-vertical"></i>

                                                {{ __('client.payment-methods.set_default') }}
                                          </span>
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('front.payment-methods.delete', ['paymentMethod' => $source->id]) }}?customer_id={{ $item->id }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button>
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                                <i class="bi bi-trash"></i>
                                            {{ __('global.delete') }}
                                          </span>
                                </button>
                            </form>
                        </div>

                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

    </div>

    <div class="py-1 px-4 mx-auto">
        {{ $paymentmethods->links('admin.shared.layouts.pagination') }}
    </div>

</div>
