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
@extends('layouts/client')
@section('title', __('helpdesk.support.create.newticket'))
@section('styles')
    <link rel="stylesheet" href="{{ Vite::asset('resources/global/css/simplemde.min.css') }}">
@endsection
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/mdeditor.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        @include('shared/alerts')
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="card">
                        <div class="card-heading">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __('helpdesk.support.create.newticket') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('helpdesk.support.create.index_description') }}
                                </p>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('front.support.create') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="grid gap-6 sm:grid-cols-2 grid-cols-2">
                                    <div class="col-span-2 sm:col-span-1">
                                        @include("shared/input", ["name" => "subject", "label" => __("helpdesk.subject"), 'value' => old('subject', $subject)])
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        @include("shared/select", ["name" => "priority", "label" => __("helpdesk.priority"), "options" => $priorities, 'value' => old('priority', $priority)])
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        @include("shared/select", ["name" => "related_id", "label" => __("helpdesk.support.create.relatedto"), "options" => $related, 'value' => old('related_id',$related)])
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="department_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ __('helpdesk.department') }}</label>
                                        <div class="relative mt-2">
                                            <select data-hs-select='{
      "toggleTag": "<button type=\"button\"><span class=\"me-2\" data-icon></span><span class=\"text-gray-800 dark:text-gray-200\" data-title></span></button>",
      "toggleClasses": "hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 relative py-3 px-4 pe-9 flex items-center text-nowrap w-full cursor-pointer bg-white border border-gray-200 rounded-lg text-start text-sm focus:border-blue-500 focus:ring-blue-500 before:absolute before:inset-0 before:z-[1] dark:bg-gray-700 dark:border-gray-700 dark:text-gray-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600",
      "dropdownClasses": "mt-2 z-50 w-full max-h-[300px] p-1 space-y-0.5 bg-white border border-gray-200 rounded-lg overflow-hidden overflow-y-auto dark:bg-slate-900 dark:border-gray-700",
      "optionClasses": "py-2 px-4 w-full text-sm text-gray-800 cursor-pointer hover:bg-gray-100 rounded-lg focus:outline-none focus:bg-gray-100 dark:bg-slate-900 dark:hover:bg-slate-800 dark:text-gray-400 dark:focus:bg-slate-800",
      "optionTemplate": "<div><div class=\"flex items-center\"><div class=\"me-2\" data-icon></div><div class=\"font-semibold text-gray-800 dark:text-gray-200\" data-title></div></div><div class=\"mt-1.5 text-sm text-gray-500\" data-description></div></div>"
    }' class="hidden" name="department_id">
                                                <option value="">Choose</option>
                                                @foreach($departments as $department)
                                                    <option value="{{ $department->id }}" {{ old('department_id', $currentdepartment) == $department->id ? 'selected' : '' }} data-hs-select-option='{
        "description": "{{ $department->trans('description') }}",
        "icon": "<i class=\"{{$department->icon}}\"></i>"
        }'>{{ $department->trans('name') }}</option>
                                                @endforeach
                                            </select>

                                            <div class="absolute top-1/2 end-3 -translate-y-1/2">
                                                <svg class="flex-shrink-0 w-3.5 h-3.5 text-gray-500 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-span-2">

                                        <label for="editor" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ __('global.content') }}</label>
                                        <textarea class="editor" name="content">{{ old('content', $content) }}</textarea>

                                    @if ($errors->has('content'))
                                            @foreach ($errors->get('content') as $error)
                                                <div class="text-red-500 text-sm mt-2">
                                                    {{ $error }}
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                    @if (setting('helpdesk_allow_attachments'))
                                    <div class="col-span-2">
                                        @include('shared/file2', ['name' => 'attachments','multiple' => true, 'label' => __('helpdesk.support.attachments'), 'help' => __('helpdesk.support.attachments_help', ['size' => setting('helpdesk_attachments_max_size'), 'types' => formatted_extension_list(setting('helpdesk_attachments_allowed_types'))])])
                                    </div>
                                        @endif
                                </div>
                                <button type="submit" class="btn btn-primary mt-2">{{ __('helpdesk.support.create.send') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
