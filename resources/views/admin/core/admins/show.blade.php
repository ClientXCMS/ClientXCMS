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
 * Year: 2025
 */
?>
?>
?>
@extends('admin/layouts/admin')
@section('title',  __($translatePrefix . '.show.title', ['name' => $item->username]))
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/flatpickr.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container mx-auto">
    @include('admin/shared/alerts')

            <div class="flex flex-col md:flex-row gap-4">
                <div class="md:w-2/3">
                    <div class="flex flex-col">
                        <div class="-m-1.5 overflow-x-auto">
                            <div class="p-1.5 min-w-full inline-block align-middle">
                                <form class="card" method="POST" action="{{ route($routePath . '.update', ['staff' => $item]) }}">
                                <div class="card-heading">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $item->id }}">
                                        @method('PUT')
                                        <div>
                                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                                {{ __($translatePrefix . '.show.title', ['name' => $item->username]) }}
                                            </h2>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ __($translatePrefix. '.show.subheading', ['date' => $item->created_at != null ?  $item->created_at->format('d/m/y') : 'None']) }}
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
                                            @include('admin/shared/input', ['name' => 'username', 'label' => __('global.username'), 'value' => old('username', $item->username)])
                                        </div>

                                        <div>
                                            @include('admin/shared/input', ['name' => 'firstname', 'label' => __('global.firstname'), 'value' => old('firstname', $item->firstname)])
                                        </div>

                                        <div>
                                            @include('admin/shared/input', ['name' => 'lastname', 'label' => __('global.lastname'), 'value' => old('lastname', $item->lastname)])
                                        </div>
                                        <div>
                                            @include('admin/shared/input', ['name' => 'email', 'label' => __('global.email'), 'value' => old('email', $item->email), 'type' => 'email'])
                                            @include('admin/shared/select', ['name' => 'locale', 'label' => __('global.locale'), 'options' => $locales, 'value' => old('locale', $item->locale)])
                                        </div>
                                        <div>
                                            @include('admin/shared/flatpickr', ['name' => 'expires_at', 'label' => __('global.expiration'),'help' => __('admin.blanktonolimit'), 'value' => old('expires_at', $item->expires_at)])
                                            @if ($item->expires_at != null)
                                                <div class="flex">
                                                    <input type="checkbox"
                                                           class="shrink-0 mt-0.5 border-gray-200 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
                                                           id="remove_expires_at" name="remove_expires_at">
                                                    <label for="remove_expires_at" class="text-sm text-gray-500 ms-3 dark:text-gray-400">{{ __('global.clicktoremove') }}</label>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            @include('admin/shared/password', ['name' => 'password', 'label' => __('global.password'), 'help' => __('admin.blanktochange')])
                                        </div>

                                        <div>
                                            @include('admin/shared/textarea', ['name' => 'signature', 'label' => __($translatePrefix . '.signature'), 'value' => old('signature', $item->signature)])
                                        </div>

                                        <div>
                                            @include('admin/shared/select', ['name' => 'role_id', 'label' => __('admin.roles.role'), 'options' => $roles, 'value' => old('role', $item->role_id)])
                                        </div>
                                    </div>
                                </form>

                                @if (staff_has_permission('admin.show_logs'))
                                    <div class="card">
                                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">{{ __($translatePrefix . '.show.login') }}</h4>
                                        @include('admin/core/actionslog/usertable', ['logs' => $logs])
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="md:w-1/3">
                    <div class="card">

                        <h4 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __($translatePrefix. '.show.details') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                @include('admin/shared/input', ['name' => 'last_ip', 'label' => __('admin.customers.show.last_ip'), 'value' => old('last_ip', $item->last_login_ip), 'disabled' => true])
                            </div>
                            <div>
                                @include('admin/shared/input', ['name' => 'last_login', 'label' => __('admin.customers.show.last_login'), 'value' => old('last_login', $item->last_login), 'disabled' => true])
                            </div>
                        </div>
                        <h4 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mt-2 mb-2">{{ __('permissions.staff_permissions') }}</h4>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($item->role->permissions->chunk(3) as $row)
                                <ul class="space-y-3 text-sm">

                                    @foreach($row as $permission)
                                        <li class="flex space-x-3">
    <span class="size-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="flex-shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
                                            <span class="text-gray-800 dark:text-gray-400">
      {{ __($permission->label) }}
    </span>
                                        </li>
                                    @endforeach
                                </ul>
                                @endforeach
                        </div>
                    </div>
                    @if (staff_has_permission('admin.show_metadata'))
                    <button class="btn btn-secondary w-full text-left mt-2" type="button" data-hs-overlay="#metadata-overlay">
                        <i class="bi bi-database mr-2"></i>
                        {{ __('admin.metadata.title') }}
                    </button>
                        @endif
                    @if (staff_has_permission('admin.show_logs'))
                        <a class="btn btn-success w-full text-left mt-2" href="{{ route('admin.logs.index') }}?filter[staff_id]={{ $item->id }}" target="_blank">
                            <i class="bi bi-journal mr-2"></i>
                            {{ __('actionslog.settings.see_staff_actions_log') }}
                        </a>
                    @endif
                </div>
            </div>
            @include('admin/metadata/overlay', ['item' => $item])
    </div>
@endsection
