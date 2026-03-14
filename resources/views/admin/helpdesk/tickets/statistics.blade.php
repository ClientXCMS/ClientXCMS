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
@section('title', __($translatePrefix . '.analytics'))
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/admin/filter.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/customcanvas.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container mx-auto">
        <div class="justify-between flex mb-4">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 my-auto">
                <a href="{{ route('admin.helpdesk.tickets.index') }}"
                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 me-2">
                    <i class="bi bi-arrow-left text-sm"></i>
                </a>
                {{ __($translatePrefix . '.analytics') }}
            </h3>
            <a class="btn btn-primary my-auto" href="{{ route('admin.helpdesk.tickets.create') }}">
                {{ __('helpdesk.admin.create_ticket') }}
            </a>
        </div>
        @include('admin/shared/alerts')
        <div class="container mx-auto">
            <div id="helpdesk-analytics">
                @if (isset($helpdesk_widgets) &&
                        $helpdesk_widgets->isNotEmpty() &&
                        staff_has_permission('admin.show_helpdesk_analytics'))
                    <div class="mb-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                        @foreach ($helpdesk_widgets as $widget)
                            <div
                                class="flex flex-col shadow-sm rounded-xl dark:bg-gray-800 bg-gray-100 hover:shadow-lg transition duration-300 ease-in-out">
                                <div class="p-4 md:p-5 flex gap-x-4">
                                    <div
                                        class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg dark:bg-slate-900 dark:border-gray-800">
                                        <i class="{{ $widget->icon }} text-black dark:text-white text-xl"></i>
                                    </div>
                                    <div class="grow">
                                        <p class="text-xs uppercase tracking-wide text-gray-500">
                                            {{ __($widget->title) }}
                                        </p>
                                        <h3
                                            class="{{ $widget->small ? 'text-sm sm:text-base' : 'text-xl sm:text-2xl' }} font-medium text-gray-800 dark:text-gray-200 mt-1">
                                            {!! $widget->value() !!}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="mb-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">

                    <div class="card">
                        <div class="card-heading !border-b-0">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                {{ __($translatePrefix . '.staff_message_counts') }}</h3>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($staff_message_counts ?? [] as $stat)
                                <div class="p-3 flex justify-between items-center">
                                    <span class="text-sm text-gray-800 dark:text-gray-200">
                                        {{ $stat->admin ? $stat->admin->excerptFullName() : __('global.deleted') }}
                                    </span>
                                    <span
                                        class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ $stat->message_count }}</span>
                                </div>
                            @empty
                                <p class="p-3 text-sm text-gray-500 text-center">{{ __('admin.dashboard.no_staff_stats') }}
                                </p>
                            @endforelse
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-heading !border-b-0">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                {{ __($translatePrefix . '.department_ticket_counts') }}</h3>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($department_ticket_counts ?? [] as $stat)
                                <div class="p-3 flex justify-between items-center">
                                    <span class="text-sm text-gray-800 dark:text-gray-200">
                                        {{ $stat->department ? $stat->department->name : __('global.deleted') }}
                                    </span>
                                    <span
                                        class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ $stat->ticket_count }}</span>
                                </div>
                            @empty
                                <p class="p-3 text-sm text-gray-500 text-center">
                                    {{ __($translatePrefix . '.no_department_stats') }}</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div class="card">
                            <div class="card-heading !border-b-0">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __($translatePrefix . '.graphs') }}</h3>
                            </div>
                            <canvas height="140" is="custom-canvas" data-hide-x-labels="true"
                                data-titles='[["{{ __('global.tickets') }}"], ["{{ __('helpdesk.admin.widgets.messages_sent') }}"]]'
                                data-labels='{!! $graph_labels !!}' data-backgrounds='["#00a65a", "#66ce64"]'
                                data-set='{!! $graph_data !!}' data-type="line" data-suffix=""
                                title="{{ __($translatePrefix . '.graphs') }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
