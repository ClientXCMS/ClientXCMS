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
@section('title', __('global.tickets'))
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/admin/filter.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/customcanvas.js')  }}" type="module"></script>

@endsection
@section('content')
    <div class="container mx-auto">
        <div class="justify-between flex mb-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 my-auto">
            {{ __('helpdesk.admin.title') }}
        </h3>
            <a class="btn btn-primary my-auto" href="{{ route($routePath . '.create') }}">
                {{ __('helpdesk.admin.create_ticket') }}
            </a>
        </div>
    @include('admin/shared/alerts')
        <div class="container mx-auto">
            @if (staff_has_permission('admin.show_helpdesk_analytics'))
            <nav class="mb-3 relative z-0 flex border rounded-xl overflow-hidden dark:border-slate-700 flex-col md:flex-row" aria-label="Tabs" role="tablist">
                    <button type="button" class="hs-tab-active:border-b-indigo-600 hs-tab-active:text-gray-900 dark:hs-tab-active:text-white relative dark:hs-tab-active:border-b-indigo-600 min-w-0 flex-1 bg-white first:border-s-0 border-s border-b-2 py-4 px-4 text-gray-500 hover:text-gray-700 text-sm font-medium text-center overflow-hidden hover:bg-gray-50 focus:z-10 focus:outline-none focus:text-indigo-600 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-800 dark:border-l-slate-700 dark:border-b-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-400 active" id="helpdesk-tickets-btn" data-hs-tab="#helpdesk-tickets" aria-controls="helpdesk-tickets" role="tab">
                        {{ __('global.tickets') }}
                    </button>
                    <button type="button" class="hs-tab-active:border-b-indigo-600 hs-tab-active:text-gray-900 dark:hs-tab-active:text-white relative dark:hs-tab-active:border-b-indigo-600 min-w-0 flex-1 bg-white first:border-s-0 border-s border-b-2 py-4 px-4 text-gray-500 hover:text-gray-700 text-sm font-medium text-center overflow-hidden hover:bg-gray-50 focus:z-10 focus:outline-none focus:text-indigo-600 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-800 dark:border-l-slate-700 dark:border-b-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-400" id="helpdesk-analytics-btn" data-hs-tab="#helpdesk-analytics" aria-controls="helpdesk-analytics" role="tab">
                        {{ __($translatePrefix . '.analytics') }}
                    </button>
            </nav>
            @endif
            <div class="hidden" id="helpdesk-analytics">
        @if (isset($helpdesk_widgets) && $helpdesk_widgets->isNotEmpty() && staff_has_permission('admin.show_helpdesk_analytics'))
            <div class="mb-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach ($helpdesk_widgets as $widget)
                    <div class="flex flex-col shadow-sm rounded-xl dark:bg-gray-800 bg-gray-100 hover:shadow-lg transition duration-300 ease-in-out">
                        <div class="p-4 md:p-5 flex gap-x-4">
                            <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg dark:bg-slate-900 dark:border-gray-800">
                                <i class="{{ $widget->icon }} text-black dark:text-white text-xl"></i>
                            </div>
                            <div class="grow">
                                <p class="text-xs uppercase tracking-wide text-gray-500">
                                    {{ __($widget->title) }}
                                </p>
                                <h3 class="{{ $widget->small ? 'text-sm sm:text-base' : 'text-xl sm:text-2xl' }} font-medium text-gray-800 dark:text-gray-200 mt-1">
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
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __($translatePrefix .'.staff_message_counts') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($staff_message_counts ?? [] as $stat)
                            <div class="p-3 flex justify-between items-center">
                                        <span class="text-sm text-gray-800 dark:text-gray-200">
                                            {{ $stat->admin ? $stat->admin->excerptFullName()  :__('global.deleted') }}
                                        </span>
                                <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ $stat->message_count }}</span>
                            </div>
                        @empty
                            <p class="p-3 text-sm text-gray-500 text-center">{{ __('admin.dashboard.no_staff_stats') }}</p>
                        @endforelse
                    </div>
                </div>
                <div class="card">
                    <div class="card-heading !border-b-0">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __($translatePrefix. '.department_ticket_counts') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($department_ticket_counts ?? [] as $stat)
                                   <div class="p-3 flex justify-between items-center">
                                        <span class="text-sm text-gray-800 dark:text-gray-200">
                                            {{ $stat->department ? $stat->department->name : __('global.deleted') }}
                                        </span>
                                <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ $stat->ticket_count }}</span>
                            </div>
                        @empty
                            <p class="p-3 text-sm text-gray-500 text-center">{{ __($translatePrefix . '.no_department_stats') }}</p>
                        @endforelse
                    </div>
                </div>
                <div class="col-span-2">
                    <div class="card">
                        <div class="card-heading !border-b-0">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __($translatePrefix . '.graphs') }}</h3>
                        </div>
                            <canvas height="140" is="custom-canvas" data-hide-x-labels="true" data-titles='[["{{ __('global.tickets') }}"], ["{{ __('helpdesk.admin.widgets.messages_sent') }}"]]' data-labels='{!! $graph_labels !!}' data-backgrounds='["#00a65a", "#66ce64"]' data-set='{!! $graph_data !!}' data-type="line" data-suffix="" title="{{ __($translatePrefix . '.graphs') }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>


        <div id="helpdesk-tickets">
            <div class="-m-1.5 overflow-x-auto">

            <div class="p-1.5 min-w-full inline-block align-middle">
            <div class="card">
                <div class="card-heading">
                <div class="!border-b-0">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __($translatePrefix . '.tickets_active') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __($translatePrefix . '.tickets_active_description') }}
                    </p>
                </div>

                @include('admin/shared/mass_actions/header', ['searchFields' => $searchFields, 'search' => $search, 'searchField' => $searchField, 'filters' => $filters, 'checkedFilters' => $checkedFilters])


                </div>
            </div>

                <div class="border rounded-lg overflow-hidden dark:border-gray-700">

                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-slate-800">

                        <tr>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      #
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('helpdesk.subject') }}
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('global.customer') }}
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('helpdesk.priority') }}
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __($translatePrefix . '.last_reply') }}
                    </span>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __($translatePrefix . '.last_update') }}
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">

                                        {{ __('global.actions') }}
                                                            </span>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                        @if (count($active_tickets) == 0)
                            <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                        <p class="text-sm text-gray-800 dark:text-gray-400">
                                            {{ __('global.no_results') }}
                                        </p>
                                    </div>
                                </td>
                        @endif
                        @foreach($active_tickets ?? [] as $item)
                            <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->id }}</span>
                    </span>
                                </td>

                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">

                          <a href="{{ route($routePath . '.show', ['ticket' => $item]) }}">
                          {{ $item->excerptSubject() }}
                          </a>
                      </span>
                    </span>
                                </td>

                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                          @if ($item->customer)
                            <a href="{{ route('admin.customers.show', ['customer' => $item->customer]) }}">
                                {{ $item->customer->excerptFullName()  }}
                            </a>
                          @else
                            {{ __('global.deleted') }}
                          @endif
                      </span>
                    </span>
                                </td>
                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400"> <x-badge-state state="{{ $item->priority }}"></x-badge-state></span>
                    </span>
                                </td>

                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                          @php($lastMessage = $item->messages->last())
                          @if ($lastMessage && $lastMessage->customer_id != null)
                              @if ($lastMessage->customer)
                                  <a href="{{ route('admin.customers.show', ['customer' => $lastMessage->customer]) }}">
                                    {{ $lastMessage->customer->excerptFullName()  }}
                                </a>
                              @else
                                  {{ __('global.deleted') }}
                              @endif
                          @elseif ($lastMessage && $lastMessage->admin_id != null)
                              @if ($lastMessage->admin)
                                  <a href="{{ route('admin.staffs.show', ['staff' => $lastMessage->admin]) }}">
                                {{ $lastMessage->admin->excerptFullName()  }}
                            </a>
                              @else
                                  {{ __('global.deleted') }}
                              @endif
                          @else
                          --
                            @endif

                      </span>
                    </span>
                                </td>
                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->updated_at != null ? $item->updated_at->format('d/m/y H:i') : 'None' }}</span>
                    </span>
                                </td>
                                <td class="h-px w-px whitespace-nowrap">

                                    <a href="{{ route($routePath . '.show', ['ticket' => $item]) }}">
                                        <span class="py-1.5">
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                             <i class="bi bi-eye-fill"></i>
                                            {{ __('global.show') }}
                                          </span>
                                        </span>
                                    </a>
                                    <form method="POST" action="{{ route($routePath . '.show', ['ticket' => $item]) }}" class="inline @if ($item->status == 'closed')confirmation-popup @endif">
                                        @method('DELETE')
                                        @csrf
                                        <button>
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                              @if ($item->status == 'closed')
                                                  <i class="bi bi-trash"></i>
                                                  {{ __('global.delete') }}
                                              @else
                                                  <i class="bi bi-x-lg"></i>
                                                  {{ __('helpdesk.support.show.close') }}
                                              @endif
                                          </span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div>
                <div class="!border-b-0">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mt-3">{{ __($translatePrefix . '.tickets_to_reply') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __($translatePrefix . '.tickets_to_reply_description') }}
                    </p>
                </div>

                <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-slate-800">

                        <tr>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      #
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('helpdesk.subject') }}
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('global.customer') }}
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('helpdesk.priority') }}
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __($translatePrefix . '.last_reply') }}
                    </span>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-start">
                                <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __($translatePrefix . '.last_update') }}
                    </span>
                                </div>
                            </th>

                            <th scope="col" class="px-6 py-3 text-start">
                                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">

                                        {{ __('global.actions') }}
                                                            </span>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                        @if (count($tickets_to_reply) == 0)
                            <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                        <p class="text-sm text-gray-800 dark:text-gray-400">
                                            {{ __('global.no_results') }}
                                        </p>
                                    </div>
                                </td>
                        @endif
                        @foreach($tickets_to_reply ?? [] as $item)
                            <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->id }}</span>
                    </span>
                                </td>

                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                          <a href="{{ route($routePath . '.show', ['ticket' => $item]) }}">
                          {{ $item->excerptSubject() }}
                          </a>
                      </span>
                    </span>
                                </td>

                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                          @if ($item->customer)
                              <a href="{{ route('admin.customers.show', ['customer' => $item->customer]) }}">
                                {{ $item->customer->excerptFullName()  }}
                            </a>
                          @else
                              {{ __('global.deleted') }}
                          @endif
                      </span>
                    </span>
                                </td>
                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400"> <x-badge-state state="{{ $item->priority }}"></x-badge-state></span>
                    </span>
                                </td>

                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                          @php($lastMessage = $item->messages->last())
                          @if ($lastMessage && $lastMessage->customer_id != null)
                              <a href="{{ route('admin.customers.show', ['customer' => $lastMessage->customer_id]) }}">
                          {{ $lastMessage->customer->excerptFullName() }}
                        </a>
                          @elseif ($lastMessage && $lastMessage->admin_id != null)
                              <a href="{{ route('admin.staffs.show', ['staff' => $lastMessage->admin_id]) }}">
                          {{ $lastMessage->admin->excerptFullName() }}
                        </a>
                          @else
                          --
                            @endif

                      </span>
                    </span>
                                </td>
                                <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->updated_at != null ? $item->updated_at->format('d/m/y H:i') : 'None' }}</span>
                    </span>
                                </td>
                                <td class="h-px w-px whitespace-nowrap">

                                    <a href="{{ route($routePath . '.show', ['ticket' => $item]) }}">
                                        <span class="py-1.5">
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                             <i class="bi bi-eye-fill"></i>
                                            {{ __('global.show') }}
                                          </span>
                                        </span>
                                    </a>
                                    <form method="POST" action="{{ route($routePath . '.show', ['ticket' => $item]) }}" class="inline @if ($item->status == 'closed')confirmation-popup @endif">
                                        @method('DELETE')
                                        @csrf
                                        <button>
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                              @if ($item->status == 'closed')
                                                  <i class="bi bi-trash"></i>
                                                  {{ __('global.delete') }}
                                              @else
                                                  <i class="bi bi-x-lg"></i>
                                                  {{ __('helpdesk.support.show.close') }}
                                              @endif
                                          </span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>
        </div>

        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="card">
                        <div class="card-heading">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __($translatePrefix . '.closed_tickets') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __($translatePrefix. '.subheading') }}
                                </p>
                            </div>

                            @include('admin/shared/mass_actions/header', ['searchFields' => $searchFields, 'search' => $search, 'searchField' => $searchField, 'filters' => $filters, 'checkedFilters' => $checkedFilters])
                            </div>
                        </div>

                    <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="mass_action_table">
                            <thead class="bg-gray-50 dark:bg-slate-800">

                                <tr>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      #
                    </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('helpdesk.subject') }}
                    </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                            {{ __('global.customer') }}
                    </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('helpdesk.priority') }}
                    </span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                      {{ __('global.created') }}
                    </span>
                                        </div>
                                    </th>

                                    <th scope="col" class="px-6 py-3 text-start">
                                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">

                                        {{ __('global.actions') }}
                                                            </span>
                                    </th>
                                </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @if (count($items) == 0)
                                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                        <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                                <p class="text-sm text-gray-800 dark:text-gray-400">
                                                    {{ __('global.no_results') }}
                                                </p>
                                            </div>
                                        </td>
                                @endif
                                @foreach($items as $item)
                                    @if (!$item->staffCanView(auth('admin')->user()))
                                        @continue
                                    @endif
                                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">

                                        <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->id }}</span>
                    </span>
                                        </td>

                                        <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->excerptSubject() }}</span>
                    </span>
                                        </td>

                                        <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                          @if ($item->customer)
                              <a href="{{ route('admin.customers.show', ['customer' => $item->customer]) }}">
                                {{ $item->customer->excerptFullName()  }}
                            </a>
                          @else
                              {{ __('global.deleted') }}
                          @endif
                      </span>
                    </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400"> <x-badge-state state="{{ $item->priority }}"></x-badge-state></span>
                    </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">
                    <span class="block px-6 py-2">
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->created_at != null ? $item->created_at->format('d/m/y') : 'None' }}</span>
                    </span>
                                        </td>
                                        <td class="h-px w-px whitespace-nowrap">

                                            <a href="{{ route($routePath . '.show', ['ticket' => $item]) }}">
                                        <span class="py-1.5">
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                             <i class="bi bi-eye-fill"></i>
                                            {{ __('global.show') }}
                                          </span>
                                        </span>
                                            </a>
                                            <form method="POST" action="{{ route($routePath . '.show', ['ticket' => $item]) }}" class="inline @if ($item->status == 'closed')confirmation-popup @endif">
                                                @method('DELETE')
                                                @csrf
                                                <button>
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                              @if ($item->status == 'closed')
                                                  <i class="bi bi-trash"></i>
                                                  {{ __('global.delete') }}
                                              @else
                                            <i class="bi bi-x-lg"></i>
                                              {{ __('helpdesk.support.show.close') }}
                                              @endif
                                          </span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="py-1 px-4 mx-auto">
                            {{ $items->links('admin.shared.layouts.pagination') }}
                        </div>
                    </div>
                </div>
        </div>
            </div>
        </div>
    </div>
@endsection
