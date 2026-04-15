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
        'name' => 'access_token',
        'label' => __('admin.settings.store.gateways.fields.access_token'),
        'value' => env('SQUARE_ACCESS_TOKEN')
        ])
    </div>
    <div>
        @include('admin/shared/input', [
        'name' => 'location_id',
        'label' => __('admin.settings.store.gateways.fields.location_id'),
        'value' => env('SQUARE_LOCATION_ID'),
        'help' => __('admin.settings.store.gateways.square.location_help')
        ])
    </div>
</div>

<div class="grid md:grid-cols-2 gap-4 grid-cols-1 mt-4">
    <div>
        @include('admin/shared/select', [
        'name' => 'environment',
        'label' => __('admin.settings.store.gateways.fields.sandbox'),
        'value' => env('SQUARE_ENVIRONMENT', 'sandbox'),
        'options' => ['sandbox' => 'Sandbox', 'production' => 'Production']
        ])
    </div>
    <div>
        @include('admin/shared/password', [
        'name' => 'webhook_signature_key',
        'label' => __('admin.settings.store.gateways.fields.webhook_secret'),
        'value' => env('SQUARE_WEBHOOK_SIGNATURE_KEY'),
        'help' => __('admin.settings.store.gateways.square.webhook_help')
        ])
    </div>
</div>