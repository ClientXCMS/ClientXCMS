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
@section('title', __($translatePrefix . '.show'))
@section('content')
<div class="container mx-auto">
    @include('admin/shared/alerts')
    <form method="POST" action="{{ route($routePath . '.update', ['cancellation_reason' => $item]) }}">

        <div class="card">
            <div class="card-heading">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ __($translatePrefix . '.show') }} #{{ $item->id }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __($translatePrefix . '.show_description') }}
                    </p>
                </div>

                <button type="submit" class="btn btn-primary">
                    {{ __('admin.updatedetails') }}
                </button>
            </div>

            @if(isset($usageCount) && $usageCount > 0)
            <div class="p-4 mx-6 mt-4 bg-blue-50 border border-blue-200 rounded-lg dark:bg-blue-900/20 dark:border-blue-800">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="bi bi-info-circle text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div class="ms-3">
                        <p class="text-sm text-blue-700 dark:text-blue-400">
                            {{ __($translatePrefix . '.usage_count', ['count' => $usageCount]) }}
                        </p>
                    </div>
                </div>
            </div>
            @endif

            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4">
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