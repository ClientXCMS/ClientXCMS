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

@extends('admin/settings/sidebar')
@section('title',  __($translatePrefix . '.show.title', ['name' => $item->name]))
@section('setting')

    <div class="container mx-auto">

    <form method="POST" class="card" action="{{ route($routePath . '.update', ['subdomains_host' => $item]) }}">
                        <div class="card-heading">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __($translatePrefix . '.show.title', ['name' => $item->domain]) }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __($translatePrefix. '.show.subheading', ['date' => $item->created_at->format('d/m/y')]) }}
                                </p>
                            </div>

                            <div class="mt-4 flex items-center space-x-4 sm:mt-0">

                                @if (staff_has_permission('admin.manage_metadata'))
                                    <button class="btn btn-secondary text-left" type="button" data-hs-overlay="#metadata-overlay">
                                        <i class="bi bi-database mr-2"></i>
                                        {{ __('admin.metadata.title') }}
                                    </button>
                                @endif
                                <button class="btn btn-primary">
                                    {{ __('admin.updatedetails') }}
                                </button>
                            </div>
                        </div>
                        @method('PUT')
                        @csrf
                            @include('admin/shared/input', ['name' => 'domain', 'label' => __($translatePrefix . '.subdomain'), 'value' => $item->domain])
                    </form>
                </div>
    @include('admin/metadata/overlay', ['item' => $item])

@endsection
