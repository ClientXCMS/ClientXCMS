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

<div class="grid md:grid-cols-4 gap-4 grid-cols-1">
    <div>
        @include('admin/shared/password', ['name' => 'public_key', 'label' => __('admin.settings.store.gateways.fields.client_id'), 'value' => env('STRIPE_PUBLIC_KEY')])
    </div>
    <div>
        @include('admin/shared/password', ['name' => 'private_key', 'label' => __('admin.settings.store.gateways.fields.client_secret'), 'value' => env('STRIPE_PRIVATE_KEY')])
    </div>
    <div>
        @include('admin/shared/password', ['name' => 'webhook_secret', 'label' => __('admin.settings.store.gateways.fields.webhook'), 'value' => env('STRIPE_WEBHOOK_SECRET', 'sandbox')])
    </div>
    <div>
        @include('admin/shared/search-select-multiple', ['name' => 'payment_types[]', 'label' => __('admin.settings.store.gateways.fields.payments_types'), 'value' => explode(',', env('STRIPE_PAYMENT_TYPES', 'card')), 'options' => $options, 'attributes' => ['multiple' => '']])
    </div>
</div>
<p class="text-gray-400">{{ __('admin.settings.store.gateways.fields.webhookhelp', ['url' => route('gateways.notification', ['gateway' => 'stripe'])]) }}</p>
