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
        'value' => env('MOLLIE_KEY')
        ])
    </div>
    <div>
        @include('admin/shared/select', [
        'name' => 'test_mode',
        'label' => __('admin.settings.store.gateways.fields.sandbox'),
        'value' => env('MOLLIE_TEST_MODE', 'true') === 'true' ? 'test' : 'live',
        'options' => ['test' => 'Test', 'live' => 'Live']
        ])
    </div>
</div>
<p class="text-gray-400 mt-2">{{ __('admin.settings.store.gateways.fields.webhookhelp', ['url' => route('gateways.notification', ['gateway' => 'mollie'])]) }}</p>