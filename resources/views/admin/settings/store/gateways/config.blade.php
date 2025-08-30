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

@extends('admin.settings.sidebar')
@section('title', $gateway->name)
@section('setting')
    <div class="card">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{$gateway->name }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
            {{ __('admin.settings.store.gateways.description', ['name' => $gateway->name]) }}
        </p>

        <form action="{{ route('admin.settings.store.gateways.save', compact('gateway')) }}" method="POST" enctype="multipart/form-data">
            @csrf
                @include('admin/shared.input', ['name' => 'name', 'label' => __('admin.settings.store.gateways.fields.name'), 'value' => $gateway->name])
                {!! $config !!}
                @method('PUT')

            @include('admin/shared.input', ['name' => 'minimal_amount', 'type' => 'number', 'label' => __('admin.settings.store.gateways.fields.minimal_amount'), 'value' => $gateway->minimal_amount], ['attributes' => ['step' => '0.01', 'min' => '0']])
            @include('admin/shared/status-select', ['value' => $gateway->status])
            <button type="submit" class="btn btn-primary mt-2">{{ __('global.save') }}</button>
        </form>
@endsection
