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
@php($fees = isset($fees) ? $fees : true)
<div class="p-1.5 min-w-full inline-block align-middle">
    <a href="#" class="text-primary" id="showmorepricingbtn">{{ __('admin.products.showmorepricing') }}</a>
    <div class="overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="pricingtable">
            <thead>
            <tr>
                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                    <button class="btn btn-primary btn-sm" type="button" data-hs-overlay="#calculator"><i
                                class="bi bi-calculator"></i></button>
                    {{ __('admin.products.tariff') }}
                </th>
                @foreach ($recurrings as $recurring)
                    <th scope="col"
                        class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase {{ $recurring['additional'] ?? false ? 'hidden' : '' }}">
                        {{ $recurring['translate'] }}
                    </th>
                @endforeach
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-200">
                    {{ __('store.price') }}
                </td>
                @foreach ($recurrings as $k => $recurring)

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200 {{ $recurring['additional'] ?? false ? 'hidden' : '' }}">
                        @include('admin.shared.input', ['name' => ($pricing_key ?? 'pricing') . '['. $k .'][price]','type' => 'number','step' => '0.01','min' => 0, 'value' => old('recurrings_' . $k . '_price', $pricing->{$k}), 'attributes' => ['data-months' => $recurring['months']]])
                    </td>
                @endforeach
            </tr>
            @if ($fees)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-200">
                    {{ __('store.fees') }}
                </td>
                @foreach ($recurrings as $k => $recurring)

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200 {{ $recurring['additional'] ?? false ? 'hidden' : '' }}">
                        @include('admin.shared.input', ['name' => ($pricing_key ?? 'pricing') . '['. $k .'][setup]', 'step' => '0.01','min' => 0, 'value' => old('recurrings_' . $k . '_setup', $pricing->{"setup_". $k}), 'attributes' => ['data-months' => $recurring['months']]])
                    </td>

                @endforeach
            </tr>
            @endif

            </tbody>
        </table>
    </div>
    @if ($errors->has(($pricing_key ?? 'pricing')))
        <p class="text-red-500 text-xs italic mt-2">
            {{ $errors->first(($pricing_key ?? 'pricing')) }}
        </p>
    @endif
</div>
