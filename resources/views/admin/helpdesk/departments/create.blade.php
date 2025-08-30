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
@section('title',  __($translatePrefix . '.create.title'))
@section('content')
    <div class="container mx-auto">

        <div class="mx-auto">
            @include('admin/shared/alerts')
                <form method="POST" class="card" action="{{ route($routePath . '.store') }}">
                    <div class="card-heading">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                {{ __($translatePrefix . '.create.title') }}
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __($translatePrefix. '.create.subheading') }}
                            </p>
                        </div>

                        <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                            <button class="btn btn-primary">
                                {{ __('admin.create') }}
                            </button>
                        </div>
                    </div>
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <div class="flex flex-col">
                            @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => old('name', $item->name)])
                        </div>
                        <div class="flex flex-col">
                            @include('admin/shared/input', ['name' => 'icon','help' => __('personalization.icon_help'), 'label' => __('personalization.icon'), 'value' => old('icon', $item->icon)])
                        </div>
                        <div class="flex flex-col">
                            @include('admin/shared/textarea', ['name' => 'description', 'label' => __('global.description'), 'value' => old('description', $item->description)])
                        </div>
                        <input type="hidden" name="id" value="{{ $item->id }}">
                    </div>
                </form>
    </div>

@endsection
