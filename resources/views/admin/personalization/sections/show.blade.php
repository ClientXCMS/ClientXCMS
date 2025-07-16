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
@section('title', __('personalization.sections.show.title'))
@section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.37.1/min/vs/editor/editor.main.css">
@endsection
@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.37.1/min/vs/loader.js"></script>
    <script>
        require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.37.1/min/vs' }});
        require(['vs/editor/editor.main'], function () {
            const editor = monaco.editor.create(document.getElementById('monaco-editor'), {
                value: @json(old('content', $content)),
                language: 'html',
                theme: {!! is_darkmode(true) ? "'vs-dark'" : "'vs'" !!},
                automaticLayout: true,
                minimap: { enabled: false },
                wordWrap: 'on',
                lineNumbers: 'on'
            });

            document.getElementById("section-form").addEventListener("submit", function (event) {
                document.querySelector("input[name='content']").value = editor.getValue();
            });
        });
    </script>
@endsection
@section('content')
    <div class="container mx-auto">

    @include('admin/shared/alerts')
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <form method="POST" action="{{ route('admin.personalization.sections.update', ['section' => $item]) }}" id="section-form">
                        @method('PUT')
                        @csrf
                    <div class="card">
                        <div class="card-heading">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ __('personalization.sections.show.title') }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('personalization.sections.show.subheading') }}
                                </p>
                            </div>
                            <div class="mt-4 flex items-center space-x-1 sm:mt-0">
                                <button class="btn btn-primary">
                                    {{ __('admin.updatedetails') }}
                                </button>
                                <a href="{{ route('admin.personalization.sections.index') }}" class="btn btn-secondary">
                                    {{ __('global.back') }}
                                </a>
                            </div>
                            </div>
                        <div class="card-body">
                            <div class="grid gap-4">
                                <div>
                                    @include('admin/shared/select', [
                                        'label' => __('personalization.theme.themename'),
                                        'name' => 'theme_uuid',
                                        'value' => $item->theme_uuid,
                                        'options' => $themes
                                    ])
                                </div>

                                <div>
                                    @include('admin/shared/select', [
                                        'label' => __('personalization.sections.fields.url'),
                                        'name' => 'url',
                                        'value' => $item->url,
                                        'options' => $pages
                                    ])
                                </div>
                                <div>
                                    <input type="hidden" name="content" value="{{ old('content', $content) }}">
                                    <div id="monaco-editor" style="height: 400px;"></div>
                                </div>
                                <div>
                                    @include('admin/shared/checkbox', [
                                        'label' => __('personalization.sections.fields.active'),
                                        'name' => 'is_active',
                                        'checked' => $item->is_active,
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
</div>

@endsection
