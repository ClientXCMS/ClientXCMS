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
@section('title', __($translatePrefix . '.show.title'))

@section('setting')
    <div class="card">
        <div class="card-heading">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    {{ __($translatePrefix . '.show.title') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __($translatePrefix . '.show.description') }}
                </p>
            </div>
            <button type="submit" form="edit-form" class="btn btn-primary">
                {{ __('admin.updatedetails') }}
            </button>
        </div>

        <form method="POST" action="{{ route($routePath . '.update', $item) }}" id="edit-form">
            @csrf
            @method('PUT')
            @include('admin.security.security_questions.form')
        </form>
    </div>
    
    @include('admin/translations/overlay', ['item' => $item])
@endsection
