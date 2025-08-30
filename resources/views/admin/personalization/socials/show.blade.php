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

    <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <form class="card" method="POST" action="{{ route($routePath .'.update', ['social' => $item]) }}" enctype="multipart/form-data">
                        <div class="card-heading">
                                @csrf
                            <input type="hidden" name="id" value="{{ $item->id }}">
                            @method('PUT')
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __($translatePrefix . '.show.title', ['name' => $item->name]) }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __($translatePrefix. '.show.subheading', ['date' => $item->created_at == null ? 'None' : $item->created_at->format('d/m/y')]) }}
                                </p>
                            </div>
                            <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                                <button class="btn btn-primary">
                                    {{ __('admin.updatedetails') }}
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => old('name', $item->name)])
                            </div>
                            <div>
                                @include('admin/shared/input', ['name' => 'url', 'label' => __('global.url'), 'value' => old('url', $item->url)])
                            </div>
                            <div>
                                @include('admin/shared/input', ['name' => 'icon', 'label' => __('personalization.icon'), 'value' => old('icon', $item->icon), 'help' => __('personalization.icon_help')])
                            </div>
                        </div>
                    </form>
            </div>
        </div>
    </div>
    </div>

@endsection
