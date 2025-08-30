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
@section('title', __('personalization.bottom_menu.title'))
@section('script')
    <script src="{{ Vite::asset('resources/global/js/sort.js') }}" type="module"></script>
@endsection
@section('setting')
    <div class="card">
        <div class="card-heading">
            <div>
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('personalization.bottom_menu.title') }}
        </h4>
        </div>

        <div>
            <button type="button" class="btn btn-primary text-sm w-full max-w-md sm:w-auto" id="saveButton" {{ $menus->count() == 0 ? 'disabled' : '' }}>{{ __('global.save') }}</button>
            <a class="btn btn-secondary mt-2 text-sm sm:ml-1 sm:mt-0 w-full max-w-md sm:w-auto" href="{{ route('admin.personalization.menulinks.create', ['type' => 'bottom']) }}">{{ __('personalization.addelement') }}</a>
        </div>
        </div>

        <ul data-button="#saveButton" data-url="{{ route('admin.personalization.menulinks.sort', ['type' => 'bottom']) }}" is="sort-list">
            @foreach ($menus as $menu)
                <li class="sortable-item {{ $menu->hasChildren() ? 'sortable-parent' : '' }}" id="{{ $menu->id }}">
                    <div class="card bg-white dark:bg-slate-900 dark:border-gray-800">
                        <div class="flex justify-between">
                            <div class="flex">
                                {!! $menu->getHtmlIcon() !!}
                                <span class="font-semibold text-gray-600 dark:text-gray-400 my-auto">{{ $menu->name }}</span>
                                @if ($menu->hasChildren())
                                    <span class="text-gray-400 dark:text-gray-600">({{ $menu->children->count() }})</span>
                                @endif
                            </div>
                                <div>
                                    <a href="{{ route('admin.personalization.menulinks.show', ['menulink' => $menu->id]) }}">
                                    <span class="py-1.5">
                                      <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                          <i class="bi bi-eye-fill"></i>
                                        {{ __('global.show') }}
                                      </span>
                                    </span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.personalization.menulinks.delete', ['menulink' => $menu->id]) }}" class="inline ml-2 confirmation-popup">
                                        @method('DELETE')
                                        @csrf
                                        <button>
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                              <i class="bi bi-trash"></i>
                                            {{ __('global.delete') }}
                                          </span>
                                        </button>
                                    </form>
                                </div>
                        </div>
                    </div>
                    @if (!empty($menu->children))
                        <ol is="sort-list2">

                            @foreach ($menu->children as $child)
                                <li class="sortable-item" id="{{ $child->id }}">
                                    <div class="card bg-white dark:bg-slate-900 dark:border-gray-800 ml-4">
                                        <div class="flex justify-between">
                                            <div class="flex">
                                                {!! $child->getHtmlIcon() !!}
                                                <span class="font-semibold text-gray-600 dark:text-gray-400 my-auto">{{ $child->name }}</span>
                                            </div>
                                            <div>
                                                    <a href="{{ route('admin.personalization.menulinks.show', ['menulink' => $child->id]) }}">
                                    <span class="py-1.5">
                                      <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                          <i class="bi bi-eye-fill"></i>
                                        {{ __('global.show') }}
                                      </span>
                                    </span>
                                                    </a>
                                                    <form method="POST" action="{{ route('admin.personalization.menulinks.delete', ['menulink' => $child->id]) }}" class="inline ml-2 confirmation-popup">
                                                        @method('DELETE')
                                                        @csrf
                                                        <button>
                                          <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                              <i class="bi bi-trash"></i>
                                            {{ __('global.delete') }}
                                          </span>
                                                        </button>
                                                    </form>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </li>
            @endforeach
        </ul>



        <form action="{{ route('admin.personalization.bottom_menu') }}" method="POST" enctype="multipart/form-data">
            @csrf
        @include('admin/shared/textarea', ['name' => 'theme_footer_description', 'label' => __('personalization.theme.fields.theme_footer_description'), 'value' => nl2br(setting('theme_footer_description')), 'help' => __('personalization.theme.fields.theme_footer_description_help'), 'translatable' => setting_is_saved('theme_footer_description')])
            @csrf
        <div>
            @include('admin/shared/textarea', ['name' => 'theme_footer_topheberg', 'label' => __('personalization.theme.fields.theme_footer_topheberg'), 'Inverifiedvalue' => setting('theme_footer_topheberg')])
        </div>
            <button type="submit" class="btn btn-primary mt-2">{{ __('global.save') }}</button>
        </form>
    </div>
    @include('admin/translations/settings-overlay', ['keys' => ['theme_footer_description' => 'textarea'], 'class' => \App\Models\Admin\Setting::class, 'id' => 0])

@endsection
