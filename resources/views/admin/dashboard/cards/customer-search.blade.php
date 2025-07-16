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

<div class="flex flex-col">
    <div class="card-heading">
        <h3 class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">{{ __('admin.dashboard.widgets.customer_search') }}</h3>
    </div>
    <div class="-m-1.5 overflow-x-auto">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="h-full">
            @include('admin/shared/input', ['name' => 'q', 'label' => __('global.lookup')])

            @include('admin/shared/select', ['name' => 'field', 'options' => $fields,'value' => 'email', 'label' => __('global.searchfrom')])
            <button class="btn btn-primary flex mt-5 w-full" type="submit">{{ __('global.search') }}</button>

        </form>
    </div>
</div>

