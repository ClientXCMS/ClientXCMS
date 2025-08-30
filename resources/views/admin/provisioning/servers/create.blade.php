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
@section('scripts')
    <script>
        window.labels = '@json($labels)';
    </script>
    <script src="{{ Vite::asset('resources/global/js/admin/server.js') }}"></script>
@endsection
@section('content')
    <div class="container mx-auto">
    @include('admin/shared/alerts')
        <div class="grid grid-cols-12 gap-4">

            <div class="col-span-12 sm:col-span-{{ $types->isEmpty() ? 12 : 8 }}">
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
                        @if ($types->isNotEmpty())

                        <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                            <button class="btn btn-primary">
                                {{ __('admin.create') }}
                            </button>
                        </div>
                            @endif
                    </div>
                    @if ($types->isEmpty())
                        <div class="col-span-12">

                        <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                            <i class="bi bi-database text-6xl text-gray-800 dark:text-gray-200"></i>
                            <p class="mt-5 text-sm text-gray-800 dark:text-gray-400">
                                {{ __($translatePrefix . '.create.enable_extension_help') }}
                            </p>
                            <a href="{{ route('admin.settings.show', ['card' => 'extensions', 'uuid' => 'extensions']) }}" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-indigo-600 hover:text-indigo-800 disabled:opacity-50 disabled:pointer-events-none dark:text-indigo-500 dark:hover:text-indigo-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">{{ __($translatePrefix . '.create.enable_extension') }}</a>
                        </div>
                        </div>
                    @else

                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div class="flex flex-col">
                            @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => $item->name])
                        </div>
                        <div class="flex flex-col">
                            @include('admin/shared/select', ['name' => 'type', 'label' => __($translatePrefix . '.type'), 'options' => $types, 'value' => $item->type])
                        </div>

                        <div class="flex flex-col">
                            @include('admin/shared/input', ['name' => 'hostname', 'label' => __($translatePrefix . '.hostname'), 'value' => old('hostname', $item->hostname)])
                        </div>

                        <div class="flex flex-col">
                            @include('admin/shared/input', ['name' => 'address', 'label' => __($translatePrefix . '.address'), 'value' => old('address', $item->address)])
                        </div>
                        <div class="flex flex-col">
                            @include('admin/shared/status-select', ['value' => $item->status])
                        </div>

                        <div class="flex flex-col">
                            @include('admin/shared/input', ['name' => 'port', 'label' => __($translatePrefix . '.port'), 'value' => old('port', $item->port), 'type' => 'number', 'min' => 1, 'max' => 65535])
                        </div>

                        <div class="flex flex-col">
                            @include('admin/shared/input', ['name' => 'username', 'label' => $labels[$item->type][0] ?? __('global.username'), 'value' => old('username', $item->username),  'attributes' => ['autocomplete' => 'off']])
                        </div>

                        <div class="flex flex-col">
                            @include('admin/shared/password', ['name' => 'password', 'label' =>  $labels[$item->type][1] ?? __('global.password'), 'value' => old('password', $item->password), 'attributes' => ['autocomplete' => 'off']])
                        </div>
                        <input type="hidden" name="id" value="{{ $item->id }}">
                    </div>
                </form>
            </div>
            <div class="col-span-12 sm:col-span-4">
                <div class="card">
                    <div class="card-heading">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200" id="">
                            {{ __($translatePrefix . '.testconnection.title') }}
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="mt-3 hidden text-gray-800 dark:text-gray-200" id="result-container">
                            <ul class="mt-3 flex flex-col">
                                <li class="inline-flex items-center gap-x-2 py-3 px-4 text-sm border text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-gray-700 dark:text-gray-200">
                                    <div class="flex items-center justify-between w-full">
                                        <strong>{{ __("global.state")}}</strong>
                                        <span id="state"></span>
                                    </div>
                                </li>
                                <li class="inline-flex items-center gap-x-2 py-3 px-4 text-sm border text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-gray-700 dark:text-gray-200">
                                    <div class="flex items-center justify-between w-full">
                                        <strong>{{ __($translatePrefix . ".testconnection.statuscode")}}</strong>
                                        <span id="statuscode"></span>
                                    </div>
                                </li>
                            </ul>

                            <div class="mt-3 overflow-y-auto
  [&::-webkit-scrollbar]:w-2
  [&::-webkit-scrollbar-track]:rounded-full
  [&::-webkit-scrollbar-track]:bg-gray-100
  [&::-webkit-scrollbar-thumb]:rounded-full
  [&::-webkit-scrollbar-thumb]:bg-gray-300
  dark:[&::-webkit-scrollbar-track]:bg-slate-700
  dark:[&::-webkit-scrollbar-thumb]:bg-slate-500" style="max-width: 100%; max-height: 200px; overflow-x: hidden; overflow: scroll;">

                                <p id="data"><strong>{{ __($translatePrefix . ".testconnection.data")}} : </strong>

                                </p>
                            </div>
                        </div>

                        <button class="btn btn-success mt-4 w-full" id="test-connection" data-fetch="{{ route('admin.servers.test') }}?">
                            <i class="bi bi-arrow-clockwise mr-2 "></i>
                            {{ __($translatePrefix . '.testconnection.button') }}
                        </button>
                    </div>
                </div>
                </div>
        </div>
        @endif
    </div>

@endsection
