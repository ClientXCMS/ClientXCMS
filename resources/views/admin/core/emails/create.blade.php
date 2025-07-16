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
@section('title',  __($translatePrefix . '.create.title'))
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/flatpickr.js') }}" type="module"></script>
    <script>
        document.querySelector('#previewButton').addEventListener('click', function() {
            const subject = document.querySelector('input[name="subject"]').value;
            const content = document.querySelector('textarea[name="content"]').value;
            const button_text = document.querySelector('input[name="button_text"]').value;
            const button_url = document.querySelector('input[name="button_url"]').value;
            const emailsValue = document.querySelector('input[name="selected_emails"]').value;
            const emails = emailsValue.split(',');
            if (emailsValue.length === 0) {
                alert('Please select at least one email');
                return;
            }
            const email = emails[0];
            let url = '{{ route('admin.emails.preview') }}?subject=' + encodeURIComponent(subject) + '&content=' + encodeURIComponent(content) + '&button_text=' + encodeURIComponent(button_text) + '&button_url=' + encodeURIComponent(button_url) + '&email=' + encodeURIComponent(email);
            window.open(url);
        });
    </script>
@endsection
@section('content')
    <div class="container mx-auto">

        <div class="mx-auto">
            @include('admin/shared/alerts')
            <form @if ($step == 2) method="POST" @endif  action="{{ route($step == 1 ? $routePath . '.create' : $routePath . '.store') }}">
                <div class="card">
                    @if ($step == 2)
                    @csrf
                    @endif
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
                            @if ($customer)
                            @if ($step == 2 && (!isset($selectedEmails) || count(explode(',', $selectedEmails)) != 0))

                                <button class="btn btn-secondary" type="button" id="previewButton">
                                    {{ __($translatePrefix . '.create.preview') }}
                                </button>
                            @endif
                            <button class="btn btn-primary">
                                {{ __($step == 1 ? 'global.next' : 'global.send') }}
                            </button>
                            @endif
                        </div>
                    </div>
                    @if ($step == 1)
                        @if (!$customer)

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
                            @include('admin/shared/search-select', ['name' => 'condition', 'label' => __($translatePrefix . '.condition'), 'value' => old('condition', ['all_customers']), 'options' => $conditions])
                        </div>
                        <div>
                            @include('admin/shared/input', ['name' => 'emails', 'label' => __($translatePrefix . '.emails'), 'value' => old('emails'), 'help' => __('global.separebycomma')])
                        </div>
                    </div>
                    @endif
                    @else
                    <div class="grid grid-cols-1">
                        @if (!empty($variables))
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mt-4">
                            {{ __($translatePrefix . '.variables') }}
                        </h3>
                        <div class="grid grid-cols-4 gap-3">
                            @foreach (collect($variables)->chunk(3) as $row)
                                <ul class="space-y-3 text-sm mt-3">

                                    @foreach($row as $variable)
                                        <li class="flex space-x-3">
                                            <i class="bi bi-wrench-adjustable shrink-0 size-4 mt-0.5 text-blue-600 dark:text-blue-500"></i>
                                            <span class="text-gray-800 dark:text-neutral-400">
                                            {{ $variable }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endforeach
                        </div>
                        @endif
                        <input type="hidden" name="condition" value="{{ $condition }}">
                        <div>
                            @include('admin/shared/input', ['name' => 'selected_emails', 'label' => __($translatePrefix . '.selected_emails'), 'value' => $selectedEmails])
                        </div>
                        <div>
                            @include('admin/shared/input', ['name' => 'subject', 'label' => __('personalization.email_templates.subject'), 'value' => old('subject', $item->subject)])
                        </div>

                        <div class="mt-4">
                            @include('admin/shared/input', ['name' => 'button_text', 'label' => __('personalization.email_templates.button_text'), 'value' => old('button_text', $item->button_text)])
                        </div>
                        <div class="mt-2">
                            @include('admin/shared/input', ['name' => 'button_url', 'label' => __($translatePrefix . '.button_url'), 'value' => old('button_url', $item->button_url), 'type' => 'url'])
                        </div>

                        <div class="mt-4">

                        </div>
                    </div>

                    <div class="grid grid-cols-1">
                        <div>
                            @include('admin/shared/textarea', ['name' => 'content', 'label' => __('global.content'), 'value' => old('content', $item->content), 'rows' => 10])
                        </div>
                        <div>
                            @include('admin/shared/flatpickr', ['name' => 'send_at', 'label' => __($translatePrefix . '.send_at'), 'value' => $item->send_at ? $item->send_at->format('Y-m-d H:i:s') : null, 'type' => 'datetime'])
                        </div>
                    </div>

                    @endif

                </div>
            </form>
        </div>
    </div>

@endsection
