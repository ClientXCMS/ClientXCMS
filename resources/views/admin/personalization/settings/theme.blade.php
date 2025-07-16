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
@extends('admin.settings.sidebar')
@section('title', __('personalization.theme.title'))
@section('setting')
    <div class="card">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('personalization.theme.title') }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
            {{ __('personalization.theme.description') }}
        </p>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Card -->
                    @foreach ($themes as $theme)
                    <div class="group flex flex-col h-full bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700 dark:shadow-slate-700/70">
                        <div class="h-52 flex flex-col justify-center items-center rounded-t-xl bg-primary" @if ($theme->hasScreenshot()) style="background: url('{{ $theme->screenshotUrl() }}'); background-size: cover;" @endif>
                        </div>
                        <div class="p-4 md:p-6">
                            @if ($currentTheme->uuid == $theme->uuid)

        <span class="block mb-1 text-xs font-semibold uppercase text-green-600 dark:text-green-500">
          {{ __('personalization.theme.enabled') }}
        </span>
                            @else
                                <span class="block mb-1 text-xs font-semibold uppercase text-red-500 dark:text-red-500">
                                    {{ __('personalization.theme.disabled') }}
                                </span>
                            @endif
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-slate-300 dark:hover:text-white">
                                {{ $theme->toDTO()->name() }}
                            </h3>
                            <p class="mt-3 text-gray-500 dark:text-slate-500 flex-grow">
                                {{ $theme->toDTO()->description() }}
                            </p>
                        </div>

                            @if ($currentTheme->uuid != $theme->uuid)
                            <form action="{{ route('admin.personalization.switch_theme', ['theme' => $theme->uuid]) }}" method="POST" class="mt-auto flex border-t border-gray-200 divide-x divide-gray-200 dark:border-slate-700 dark:divide-slate-700">
                            @csrf
                                <a class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-es-xl bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-white dark:hover:bg-slate-800 dark:focus:bg-slate-800" href="{{ $theme->demoUrl() }}">
                                    {{ __('personalization.demo') }}
                                </a>
                                @if ($theme->toDTO()->isActivable())
                                <button type="submit" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-ee-xl bg-green-100 text-green-800 shadow-sm hover:bg-green-50 focus:outline-none focus:bg-green-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-white dark:hover:bg-slate-800 dark:focus:bg-slate-800">
                                    <i class="bi bi-arrow-repeat"></i>
                                    {{ __('extensions.settings.enable') }}
                                </button>
                                @else
                                    <a class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-ee-xl bg-indigo-300 text-indigo-800 shadow-sm hover:bg-indigo-50 focus:outline-none focus:bg-indigo-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-white dark:hover:bg-slate-800 dark:focus:bg-slate-800" href="https://clientxcms.com/marketplace/{{ $theme->uuid }}">
                                        <i class="bi bi-cart"></i>
                                        {{ __('extensions.settings.buy') }}
                                    </a>
                                    @endif
                            </form>
                                @endif
                    </div>
                    @endforeach
                </div>
                @method('PUT')
            </div>


        <form method="POST" action="{{ route('admin.personalization.config_theme', ['theme' => $currentTheme->uuid]) }}" class="card mt-3" enctype="multipart/form-data">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    @include('admin/shared/input', ['name' => 'theme_home_title', 'label' => __('personalization.home.fields.theme_home_title'), 'value' => setting('theme_home_title', setting('app.name')), 'translatable' => setting_is_saved('theme_home_title')])
                </div>
                <div>
                    @include('admin/shared/input', ['name' => 'theme_home_subtitle', 'label' => __('personalization.home.fields.theme_home_subtitle'), 'value' => setting('theme_home_subtitle', "Hébergeur français de qualité utilisant la nouvelle version Next Gen de CLIENTXCMS."), 'translatable' => setting_is_saved('theme_home_subtitle')])
                </div>
                <div>
                    @include('admin/shared/file', ['name' => 'theme_home_image', 'label' => __('personalization.home.fields.theme_home_image'), 'canRemove' => true])
                </div>
                <div>
                    @include('admin/shared/select', ['name' => 'theme_switch_mode', 'label' => __('personalization.theme.fields.theme_switch_mode.title'), 'value' => setting('theme_switch_mode'), 'options' => $modes])
                    @include('admin/shared/checkbox', ['name' => 'theme_header_logo', 'label' => __('personalization.theme.fields.theme_header_logo'), 'checked' => setting('theme_header_logo')])
                </div>
            </div>
            <p class="text-gray-800 dark:text-neutral-400">
                @csrf
                {!! $configHTML !!}
            </p>

            <button type="submit" class="btn btn-primary mt-2">{{ __('global.save') }}</button>
        </form>

        @include('admin/translations/settings-overlay', ['keys' => [
        'theme_home_title' => 'text',
        'theme_home_subtitle' => 'text',
        'theme_home_title_meta' => 'text',
    ], 'class' => \App\Models\Admin\Setting::class, 'id' => 0])
@endsection
