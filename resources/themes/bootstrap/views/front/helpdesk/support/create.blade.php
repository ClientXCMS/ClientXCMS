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
    <div class="container py-5">
        @include('shared/alerts')
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h4">{{ __('helpdesk.support.create.newticket') }}</h2>
                        <p class="text-muted small">{{ __('helpdesk.support.create.index_description') }}</p>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('front.support.create') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    @include("shared/input", ["name" => "subject", "label" => __("helpdesk.subject"), 'value' => old('subject', $subject)])
                                </div>
                                <div class="col-md-6">
                                    @include("shared/select", ["name" => "priority", "label" => __("helpdesk.priority"), "options" => $priorities, 'value' => old('priority', $priority)])
                                </div>
                                <div class="col-md-6">
                                    @include("shared/select", ["name" => "related_id", "label" => __("helpdesk.support.create.relatedto"), "options" => $related, 'value' => old('related_id', $related)])
                                </div>
                                <div class="col-md-6">
                                    <label for="department_id" class="form-label mt-2">{{ __('helpdesk.department') }}</label>
                                    <select class="form-select" name="department_id" id="department_id">
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" {{ old('department_id', $currentdepartment) == $department->id ? 'selected' : '' }}>
                                                {{ $department->trans('name') }} - {{ $department->trans('description') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="editor" class="form-label">{{ __('global.content') }}</label>
                                    <textarea class="editor" name="content" class="form-control">{{ old('content', $content) }}</textarea>
                                    @if ($errors->has('content'))
                                        @foreach ($errors->get('content') as $error)
                                            <div class="text-danger small mt-2">
                                                {{ $error }}
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                @if (setting('helpdesk_allow_attachments'))
                                    <div class="col-12">
                                        @include('shared/file2', ['name' => 'attachments', 'label' => __('helpdesk.support.attachments'), 'help' => __('helpdesk.support.attachments_help', ['size' => setting('helpdesk_attachments_max_size'), 'types' => formatted_extension_list(setting('helpdesk_attachments_allowed_types'))])])
                                    </div>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">{{ __('helpdesk.support.create.send') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
