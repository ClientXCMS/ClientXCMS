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

<div class="grid md:grid-cols-2 gap-4 grid-cols-1">
    <div>
        @include('admin/shared/password', [
        'name' => 'api_key',
        'label' => __('admin.settings.store.gateways.fields.api_key'),
        'value' => env('SUMUP_API_KEY'),
        'help' => __('admin.settings.store.gateways.sumup.api_key_help')
        ])
    </div>
    <div>
        @include('admin/shared/input', [
        'name' => 'merchant_code',
        'label' => __('admin.settings.store.gateways.fields.merchant_code'),
        'value' => env('SUMUP_MERCHANT_CODE'),
        'help' => __('admin.settings.store.gateways.sumup.merchant_help')
        ])
    </div>
</div>