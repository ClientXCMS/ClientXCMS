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
@section('title',  __($translatePrefix . '.show.title', ['name' => $item->name]))
@section('styles')
    <link rel="stylesheet" href="{{ Vite::asset('resources/global/css/editor.scss') }}">
@endsection
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/editor.js') }}" type="module"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.37.1/min/vs/loader.js"></script>

    <script src="{{ Vite::asset('resources/global/js/clipboard.js') }}" type="module"></script>
    <script>
        let editorMode = 'editor';
        function toggleEditor() {
            const editor = document.getElementById('monaco-editor');
            const description = document.getElementsByClassName('editor-description')[0];
            const toggleBtn = document.getElementById('toggle-btn');
            if (editorMode === 'html') {
                editorMode = 'editor';

                editor.style.display = 'none';
                description.style.display = 'block';

                toggleBtn.innerHTML = 'HTML';
            } else {
                editorMode = 'html';

                editor.style.display = 'block';
                description.style.display = 'none';

                toggleBtn.innerHTML = 'Editor';
            }
        }
        require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.37.1/min/vs' }});
        require(['vs/editor/editor.main'], function () {
            const editor = monaco.editor.create(document.getElementById('monaco-editor'), {
                value: @json(old('description', $item->description)),
                language: 'html',
                theme: {!! is_darkmode('admin') ? "'vs-dark'" : "'vs'" !!},
                automaticLayout: true,
                minimap: { enabled: false },
                wordWrap: 'on',
                lineNumbers: 'on'
            });

            document.getElementById("product-form").addEventListener("submit", function (event) {
                if (editorMode === 'html') {
                    document.querySelector("textarea[name='description']").innerHTML = editor.getValue();
                } else {
                    document.querySelector("textarea[name='description']").innerHTML = document.querySelector('#editor_value-description').innerHTML;
                }
            });
        });
    </script>
    <script src="{{ Vite::asset('resources/global/js/admin/productshow.js') }}" type="module"></script>
    <script src="{{ Vite::asset('resources/global/js/admin/pricing.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container mx-auto">

            @include('admin/shared/alerts')
            <form method="POST" action="{{ route($routePath .'.update', ['product' => $item]) }}" enctype="multipart/form-data" id="product-form">
                <div class="flex flex-col">
                    <div class="-m-1.5 overflow-x-auto">
                        <div class="p-1.5 min-w-full inline-block align-middle">
                            <div class="card">
                                <div class="card-heading">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                            {{ __($translatePrefix . '.show.title', ['name' => $item->name]) }}
                                        </h2>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ __($translatePrefix. '.show.subheading', ['date' => $item->created_at->format('d/m/y')]) }}
                                        </p>
                                    </div>
                                    <div class="mt-4 flex items-center space-x-4 sm:mt-0">
                                        <button type="button" id="btn-config" class="btn btn-success text-left" data-hs-overlay="#config-overlay">
                                            <i class="bi bi-pencil-square mr-2"></i>
                                            {{ __($translatePrefix . '.config.btn') }}
                                        </button>
                                        @if (staff_has_permission('admin.manage_metadata'))
                                        <button class="btn btn-secondary text-left" type="button" data-hs-overlay="#metadata-overlay">
                                            <i class="bi bi-database mr-2"></i>
                                            {{ __('admin.metadata.title') }}
                                        </button>
                                        @endif
                                        <button class="btn btn-primary">
                                            {{ __('admin.updatedetails') }}
                                        </button>

                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        @include('admin/shared/input', ['name' => 'name', 'label' => __('global.name'), 'value' => old('name', $item->name), 'translatable' => true])
                                    </div>
                                    <div>
                                        @include('admin/shared/input', ['name' => 'stock', 'label' => __($translatePrefix . '.stock'), 'type' => 'number', 'value' => old('stock', $item->stock)])
                                    </div>

                                    <div>
                                        @include('admin/shared/select', ['name' => 'type', 'label' => __($translatePrefix . '.type'), 'value' => old('type', $item->type), 'options' => $types])
                                    </div>
                                    <div>
                                        @include('admin/shared/status-select', ['name' => 'status', 'label' => __('global.status'), 'value' => old('status', $item->status)])
                                        @include('admin/shared/select', ['name' => 'group_id', 'label' => __($translatePrefix . '.group'), 'value' => old('group_id', $item->group_id), 'options' => $groups])
                                        @include('admin/shared/input', ['name' => 'sort_order', 'label' => __('global.sort_order'), 'value' => old('sort_order', $item->sort_order)])

                                        <div class="mt-2 flex">
                                            <input type="text" readonly class="input-text" id="basket_url" value="{{ route('front.store.basket.add', ['product' => $item]) }}">
                                            <button type="button" data-clipboard-target="#basket_url" data-clipboard-action="copy" data-clipboard-success-text="Copied" class=" js-clipboard w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent bg-blue-600 text-white hover:bg-blue-700  dark:focus:ring-1 dark:focus:ring-gray-600">
                                                <svg class="js-clipboard-default w-4 h-4 group-hover:rotate-6 transition" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>

                                                <svg class="js-clipboard-success hidden w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>

                                            </button>
                                        </div>
                                        <div class="mt-2">
                                            @include('admin/shared/checkbox', ['name' => 'pinned', 'label' => __('global.pinned'), 'checked' => old('pinned', $item->pinned)])
                                        </div>
                                        @include('admin/shared/file', ['name' => 'image', 'label' => __('admin.groups.image'), 'help' => __('admin.blanktochange'), 'canRemove' => true])

                                    </div>
                                    <div class="col-span-2">
                                            @include('admin/shared/editor', ['name' => 'description', 'label' => __('global.description') . '<a href="#" onclick="toggleEditor(); return false;" id="toggle-btn" class="ml-5 btn btn-outline-primary btn-sm mb-2">HTML</a>', 'value' => old('description', $item->description), 'translatable' => true])

                                        <div id="monaco-editor" style="height: 400px;display:none;"></div>
                                    </div>

                                </div>
                            </div>
                            <div class="card mt-2">
                                <div class="card-body">
                                    <div class="flex flex-col">
                                        <div class="-m-1.5 overflow-x-auto">
                                            @include('admin/shared/pricing/table')
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div id="config-overlay" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">
                    {{ __($translatePrefix . '.config.title') }}
                </h3>
                <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#config-overlay">
                    <span class="sr-only">{{ __('global.closemodal') }}</span>
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="p-4">
                <form method="POST" action="{{ route($routePath . '.config', ['product' => $item]) }}">

                    {!! $configForm !!}
                    @csrf
                    <button class="btn btn-primary mt-2 w-full">{{ __('global.save') }}</button>

                </form>
            </div>
        </div>

        <div id="calculator" class="hs-overlay hs-overlay-open:translate-x-0 hidden translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-xs w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700" tabindex="-1">
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">
                    {{ __($translatePrefix . '.calculator.title') }}
                </h3>
                <button type="button" class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-overlay="#calculator">
                    <span class="sr-only">{{ __('global.close') }}</span>
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="p-4">
                <p class="text-gray-800 dark:text-gray-400">
                    {{ __($translatePrefix . '.calculator.subheading') }}
                    @include('admin/shared/input', ['name' => 'percentage', 'help' => __($translatePrefix . '.calculator.help'), 'label' => __($translatePrefix . '.calculator.percent'), 'value' => 5])
                </p>
                <button type="button" class="btn btn-primary mt-2" id="calculatorBtn" data-hs-overlay="#calculator">
                    {{ __($translatePrefix . '.calculator.apply') }}
                </button>
            </div>
        </div>
        @include('admin/metadata/overlay', ['item' => $item])
        @include('admin/translations/overlay', ['item' => $item])
    @endsection
