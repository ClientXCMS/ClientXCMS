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
@php ($hasCustomer = \App\Models\Account\Customer::first() != null)
@section('title',  __($translatePrefix . '.create.title'))
@section('styles')
    <link rel="stylesheet" href="{{ Vite::asset('resources/global/css/simplemde.min.css') }}">
    <link rel="stylesheet" href="{{ Vite::asset('resources/global/css/tomselect.scss') }}">
@endsection
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/mdeditor.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/tomselect.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container mx-auto">

        <div class="mx-auto">
            @include('admin/shared/alerts')
            @if ($currentCustomer)
                <form method="POST" action="{{ route($routePath . '.store') }}?customer_id={{ $customer->id }}" enctype="multipart/form-data">
                    @else
                        <form>
                            @endif
                    <div class="card">
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
                        @if ($currentCustomer)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @csrf
                                <div class="col-span-2 sm:col-span-1">
                                    @include("admin/shared/input", ["name" => "subject", "label" => __("helpdesk.subject")])
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    @include("admin/shared/select", ["name" => "priority", "label" => __("helpdesk.priority"), "options" => $priorities, 'value' => old('priority')])
                                </div>
                                <div  class="col-span-2 sm:col-span-1">
                                    @include("admin/shared/select", ["name" => "related_id", "label" => __("helpdesk.support.create.relatedto"), "options" => $related, 'value' => old('related_id', 'none')])
                                </div>
                                <div  class="col-span-2 sm:col-span-1">
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
        "description": "{{ $department->description }}",
        "icon": "<i class=\"{{$department->icon}}\"></i>"
        }'>{{ $department->name }}</option>
                                            @endforeach
                                        </select>

                                        <div class="absolute top-1/2 end-3 -translate-y-1/2">
                                            <svg class="flex-shrink-0 w-3.5 h-3.5 text-gray-500 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-span-2">
                                    <label for="editor" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ __('global.content') }}</label>
                                    <textarea id="editor" name="content" class="editor">{{ old('content', auth('admin')->user()->getTicketSignature($customer)) }}</textarea>
                                @if ($errors->has('content'))
                                        @foreach ($errors->get('content') as $error)
                                            <div class="text-red-500 text-sm mt-2">
                                                {{ $error }}
                                            </div>
                                        @endforeach
                                    @endif

                                        <div class="mt-2">
                                            @include('admin/shared/file2', ['name' => 'attachments', 'label' => __('helpdesk.attachments'), 'multiple' => true, 'help' => __('helpdesk.support.attachments_help', ['size' => setting('helpdesk_attachments_max_size'), 'types' => formatted_extension_list(setting('helpdesk_attachments_allowed_types'))])])
                                        </div>
                                </div>
                            </div>
                        </div>
                            @else
                                @if (!$hasCustomer)
                                    <div class="col-span-12">

                                        <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                                            <i class="bi bi-people text-6xl text-gray-800 dark:text-gray-200"></i>
                                            <p class="mt-5 text-sm text-gray-800 dark:text-gray-400">
                                                {{ __('admin.customers.create.create_customer_help') }}
                                            </p>
                                            <a href="{{ route('admin.customers.create') }}" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-indigo-600 hover:text-indigo-800 disabled:opacity-50 disabled:pointer-events-none dark:text-indigo-500 dark:hover:text-indigo-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">{{ __('admin.customers.create.title') }}</a>
                                        </div>
                                    </div>
                                @else
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                <div>
                                    @include('admin/shared/search-field', ['name' => 'customer_id', 'label' => __('global.customer'), 'apiUrl' => route('admin.customers.search'), 'options' => $item->customer ? [$item->customer_id => $item->customer->excerptFullName()] : [], 'value' => $item->customer_id])
                                    </div>

                                    <div>
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
        "description": "{{ $department->description }}",
        "icon": "<i class=\"{{$department->icon}}\"></i>"
        }'>{{ $department->name }} </option>
                                                @endforeach
                                            </select>

                                            <div class="absolute top-1/2 end-3 -translate-y-1/2">
                                                <svg class="flex-shrink-0 w-3.5 h-3.5 text-gray-500 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    @endif
                            @endif
                </form>
        </div>
    </div>

@endsection
