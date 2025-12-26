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

@extends('admin/layouts/admin')
@section('title', __($translatePrefix . '.create'))
@section('content')
<div class="container mx-auto">
    @include('admin/shared/alerts')
    <form method="POST" action="{{ route($routePath . '.store') }}">
        <div class="card">
            <div class="card-heading">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ __($translatePrefix . '.create') }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __($translatePrefix . '.create_description') }}
                    </p>
                </div>

                <button type="submit" class="btn btn-primary">
                    {{ __('admin.create') }}
                </button>
            </div>

            @csrf

            <div class="grid grid-cols-1 gap-4 p-6">
                <div>
                    @include('admin/shared/input', ['name' => 'reason', 'label' => __($translatePrefix . '.reason'), 'value' => $item->reason])
                </div>

                <div>
                    @include('admin/shared/status-select', ['name' => 'status', 'label' => __('global.status'), 'value' => old('status', $item->status)])
                </div>
            </div>
        </div>
    </form>
</div>
@endsection