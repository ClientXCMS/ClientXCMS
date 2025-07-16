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
@extends('admin.settings.sidebar')
@section('title', __('helpdesk.admin.settings.title'))
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
@endsection
@section('setting')
    <div class="card">
        <div class="flex justify-between">

        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('helpdesk.admin.settings.title') }}
        </h4>
        <div class="hs-tooltip [--trigger:click]">
            <div class="hs-tooltip-toggle block text-center">
                <button type="button" class="inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-blue-600 hover:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400">
                    {{ __('global.preview') }}
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m18 15-6-6-6 6"></path>
                    </svg>
                </button>

                <div class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible hidden opacity-0 transition-opacity absolute invisible z-10 max-w-xs w-full bg-white border border-gray-100 text-start rounded-xl shadow-md dark:bg-neutral-800 dark:border-neutral-700" role="tooltip">
                    <div class="p-4">
                        <div class="mb-3 flex justify-between items-center gap-x-3">
                            <img src="https://cdn.clientxcms.com/ressources/docs/ticket.png">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <div>
            <form method="POST" action="{{ route('admin.settings.helpdesk') }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        @include('admin/shared/input', ['name' => 'helpdesk_ticket_auto_close_days', 'label' => __('helpdesk.admin.settings.fields.ticket_auto_close_days'), 'value' => setting('helpdesk_ticket_auto_close_days'), 'help' => __('helpdesk.admin.settings.fields.ticket_auto_close_days_help')])
                    </div>
                    <div>
                        @include('admin/shared/input', ['name' => 'helpdesk_webhook_url', 'label' => __('helpdesk.admin.settings.fields.webhook_url'), 'value' => setting('helpdesk_webhook_url')])
                    </div>

                    <div>
                        @include('admin/shared/input', ['name' => 'helpdesk_reopen_days', 'label' => __('helpdesk.admin.settings.fields.reopen_days'), 'value' => setting('helpdesk_reopen_days'), 'help' => __('helpdesk.admin.settings.fields.reopen_days_help')])
                    </div>
                    <div class="relative flex items-start mr-3 mt-3 col-span-2">
                        <div class="flex items-center h-5 mt-1">
                            <input id="hs-checkbox-delete" name="helpdesk_allow_attachments" {{ setting('helpdesk_allow_attachments') ? 'checked' : '' }} type="checkbox" class="hs-collapse-toggle border-gray-200 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"  data-hs-collapse="#hs-smtp" aria-describedby="hs-smtp-description">
                        </div>
                        <label for="hs-checkbox-delete" class="ms-3">
                            <span class="block text-sm font-semibold text-gray-800 dark:text-gray-300">{{ __('helpdesk.admin.settings.fields.allow_attachments') }}</span>
                            <span id="hs-smtp-description" class="block text-sm text-gray-600 dark:text-gray-500">{{ __('helpdesk.admin.settings.fields.allow_attachments_help') }}</span>
                        </label>
                    </div>
                    <div>
                        @include('admin/shared/input', ['name' => 'helpdesk_attachments_max_size', 'label' => __('helpdesk.admin.settings.fields.attachments_max_size'), 'value' => setting('helpdesk_attachments_max_size')])
                    </div>
                    <div>
                        @include('admin/shared/input', ['name' => 'helpdesk_attachments_allowed_types', 'label' => __('helpdesk.admin.settings.fields.attachments_allowed_types'), 'value' => setting('helpdesk_attachments_allowed_types'), 'help' => __('helpdesk.admin.settings.fields.attachments_allowed_types_help')])
                    </div>
                    <div>

                    </div>
                </div>

                    <button type="submit" class="btn btn-primary">{{ __('global.save') }}</button>
            </form>
        </div>

    </div>

@endsection
