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
@section('title',  __($translatePrefix . '.show.title', ['name' => $translations[$item->name] ?? $item->name]))
@section('content')
    <div class="container mx-auto">

    <div class="mx-auto">

            @include('shared/alerts')
            <form method="POST" class="card" action="{{ route($routePath . '.update', ['email_template' => $item]) }}">
                <div class="card-heading">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            {{ __($translatePrefix . '.show.title', ['name' => $translations[$item->name] ?? $item->name]) }}
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
                @method('PUT')
                @csrf
                <div class="grid gap-4">

                    <div class="flex flex-col">
                        @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'help' => __($translatePrefix . '.shouldnotchanged'),'value' => old('name', $item->name)])
                    </div>
                    <div class="flex flex-col">
                        @include('admin/shared/input', ['name' => 'button_text', 'label' => __($translatePrefix . '.button_text'), 'value' => old('button_text', $item->button_text)])
                    </div>

                    <div class="flex flex-col">
                        @include('admin/shared/input', ['name' => 'subject', 'label' => __($translatePrefix . '.subject'), 'value' => old('subject', $item->subject)])
                    </div>
                    <div class="flex flex-col">
                        @include('admin/shared/textarea', ['name' => 'content', 'label' => __('global.content'), 'Inverifiedvalue' => old('content', $item->content), 'rows' => 10])
                    </div>
                    <input type="hidden" name="id" value="{{ $item->id }}">
                    <div>
                        @include('admin/shared/checkbox', ['name' => 'hidden', 'label' => __('global.hidden'), 'checked' => old('hidden', $item->hidden)])
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
