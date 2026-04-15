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

@extends('admin.settings.sidebar')
@section('title', __($translatePrefix . '.create.title'))

@section('setting')
<div class="card">
    <div class="card-heading">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                {{ __($translatePrefix . '.create.title') }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __($translatePrefix . '.create.description') }}
            </p>
        </div>
        <button type="submit" form="create-form" class="btn btn-primary">
            {{ __('admin.create') }}
        </button>
    </div>

    <form method="POST" action="{{ route($routePath . '.store') }}" id="create-form">
        @csrf
        @include('admin.security.security_questions.form')
    </form>
</div>
@endsection