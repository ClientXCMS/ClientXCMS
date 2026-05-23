<?php
/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */
?>
@extends('layouts/front')
@section('title', __('helpdesk.guest.title'))
@section('content')
    <div class="max-w-2xl mx-auto px-4 py-10">
        @include('shared/alerts')
        <div class="card">
            <div class="card-heading">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    {{ __('helpdesk.guest.title') }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('helpdesk.guest.intro') }}
                </p>
            </div>

            <form method="POST" action="{{ route('front.support.guest.store') }}" class="mt-4 space-y-4">
                @csrf

                <div>
                    @include('shared/input', [
                        'name' => 'email',
                        'type' => 'email',
                        'label' => __('global.email'),
                        'required' => true,
                        'value' => old('email'),
                        'help' => __('helpdesk.guest.email_help'),
                    ])
                </div>

                <div>
                    @include('shared/input', [
                        'name' => 'name',
                        'label' => __('global.name'),
                        'value' => old('name'),
                    ])
                </div>

                @if (!$departments->isEmpty())
                    <div>
                        @include('shared/select', [
                            'name' => 'department_id',
                            'label' => __('helpdesk.department'),
                            'options' => $departments->pluck('name', 'id')->toArray(),
                            'value' => old('department_id'),
                        ])
                    </div>
                @endif

                <div>
                    @include('shared/input', [
                        'name' => 'subject',
                        'label' => __('helpdesk.subject'),
                        'required' => true,
                        'value' => old('subject'),
                    ])
                </div>

                <div>
                    @include('shared/textarea', [
                        'name' => 'message',
                        'label' => __('helpdesk.message'),
                        'required' => true,
                        'value' => old('message'),
                        'rows' => 8,
                    ])
                </div>

                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('helpdesk.guest.disclaimer') }}
                    </p>
                    <button type="submit" class="btn btn-primary">
                        {{ __('helpdesk.guest.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
