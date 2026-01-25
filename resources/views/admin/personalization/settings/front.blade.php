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
@section('title', __('personalization.front_menu.title'))
@section('script')
    <script src="{{ Vite::asset('resources/global/js/admin/menu-inline-editor.js') }}" type="module"></script>
@endsection
@section('setting')
    <div class="card">
        <div class="card-heading">
            <div>
                <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
                    {{ __('personalization.front_menu.title') }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('personalization.front_menu.description') }}
                </p>
            </div>
            <div>
                <a class="btn btn-secondary text-sm" href="{{ route('admin.personalization.menulinks.create', ['type' => 'front']) }}">
                    <i class="bi bi-plus-lg mr-1"></i>
                    {{ __('personalization.addelement') }}
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert text-red-700 bg-red-100 mt-2" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                @foreach ($errors->all() as $error)
                    {!! $error !!}<br/>
                @endforeach
            </div>
        @endif

        @include('admin.personalization.menu_links._inline-editor', [
            'menus' => $menus,
            'type' => 'front',
            'roles' => $roles,
            'linkTypes' => $linkTypes,
            'supportDropDropdown' => $supportDropDropdown
        ])
    </div>
@endsection
