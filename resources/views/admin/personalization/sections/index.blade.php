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

@extends('admin/layouts/admin')
@section('title', __('personalization.sections.title'))
@section('scripts')
<script src="{{ Vite::asset('resources/global/js/sort.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container mx-auto">

    @include('admin/shared/alerts')
        @if (!empty($pages))
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">

                    <div class="grid grid-cols-6 gap-4">

                        <div class="col-span-6">
                            @if ($active_page == null)
                            <div class="card">
                                <h3 class="text-xl mb-2 font-semibold text-gray-800 dark:text-gray-200 hidden sm:block">
                                    {{ __('personalization.sections.pages.title') }}
                                </h3>
                                <p class="text-sm mb-2 text-gray-600 dark:text-gray-400 hidden sm:block">
                                    {{ __('personalization.sections.pages.subheading') }}
                                </p>
                                <div class="flex flex-wrap">
                                    <nav class="grid grid-cols-3 gap-4" role="tablist" aria-orientation="horizontal">
                                        @foreach($pages as $uuid => $item)

                                            <a class="{{ $active_page == $uuid ? 'border' : '' }} bg-white p-4 transition duration-300 rounded-lg hover:bg-gray-100 dark:bg-slate-900 dark:border-gray-800 dark:hover:bg-white/[.05] dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="?active_page={{ $uuid }}" >
                                                <div class="flex">
                                                    <div class="mt-1.5 flex justify-center flex-shrink-0 rounded-s-xl">
                                                        <i class="w-5 h-5 text-gray-800 dark:text-gray-200 {{ $item['icon'] ?? 'bi bi-people' }}" style="font-size: 25px"></i>
                                                    </div>

                                                    <div class="grow ms-6">
                                                        <h3 class="text-sm font-semibold text-indigo-600 dark:text-indigo-600">
                                                            {{ $item['title'] }}
                                                        </h3>
                                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-500">
                                                            {{ __('personalization.sections.pages.description', ['name' => $item['title']]) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </nav>
                                </div>
                            </div>
                                @endif
                        </div>

                    @if ($active_page)

                            <div class="col-span-6">
                                <div class="card">
                                    <div class="flex justify-between">
                                        <div>
                                            <h1 class="text-xl mb-2 font-semibold text-gray-800 dark:text-gray-200">
                                                {{ __('personalization.sections.title') }}
                                            </h1>
                                            <p class="text-sm mb-2 text-gray-600 dark:text-gray-400">
                                                {{ __('personalization.sections.subheading', ['name' => $active_page['title']]) }}
                                            </p>
                                        </div>
                                        <div>
                                        <a href="{{ route('admin.personalization.sections.index') }}" class="btn btn-secondary my-auto">
                                            {{ __('global.back') }}
                                        </a>
                                        <button type="button" class="btn btn-primary" id="saveButton-{{ $uuid }}">{{ __('global.save') }}</button>
                                        </div>
                                    </div>
                                    @if ($active_page['sections']->isEmpty())


                                        <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                                            <i class="bi bi-layout-text-sidebar text-6xl text-gray-800 dark:text-gray-200"></i>
                                            <p class="mt-5 text-sm text-gray-800 dark:text-gray-400 text-center">
                                                {!! __('personalization.sections.empty') !!}
                                            </p>
                                        </div>
                                    @endif
                        <div class="flex flex-wrap">
                            <ul class="w-full" data-button="#saveButton-{{ $uuid }}" data-url="{{ route('admin.personalization.sections.sort') }}" is="sort-list">
                                       @foreach ($active_page['sections'] as $section)
                                           <li class="flex items-center justify-between py-2 px-4 dark:border-neutral-700 rounded-lg mb-2 sortable-item" id="{{ $section->id }}">
                                               <div class="max-h-[500px] group relative flex flex-col h-full bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700 dark:shadow-slate-700/70">
                                                   <div class="hs-dropdown absolute top-3 left-3 z-10">
                                                       <button id="hs-dropdown-custom-icon-trigger" type="button" class="hs-dropdown-toggle flex justify-center items-center size-9 text-sm font-semibold rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:p-3 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 dark:focus:bg-neutral-800 p-3" aria-haspopup="menu" aria-expanded="false" aria-label="Dropdown">
                                                           <i class="bi bi-three-dots"></i>
                                                       </button>
                                                       <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-md rounded-lg p-1 space-y-0.5 mt-2 dark:bg-neutral-800 dark:border dark:border-neutral-700" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-custom-icon-trigger">
                                                           <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700" href="{{ route($routePath . '.show', ['section' => $section]) }}" {{ !$section->isModifiable() ? 'disabled="true"' : '' }}>
                                                               {{ __('global.edit') }}
                                                           </a>
                                                           @if ($section->isConfigurable())
                                                           <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-blue-600 hover:bg-blue-50 focus:outline-none focus:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20 dark:hover:text-blue-300 dark:focus:bg-blue-900/20" href="{{ route($routePath . '.config', ['section' => $section]) }}">
                                                               <i class="bi bi-gear"></i>
                                                               {{ __('personalization.sections.config.btn') }}
                                                           </a>
                                                           @endif
                                                           <form method="POST" action="{{ route($routePath . '.clone', ['section' => $section]) }}">
                                                               @csrf
                                                               <button class="flex items-center gap-x-3.5 py-2 w-full px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700">
                                                                   {{ __('global.clone') }}
                                                               </button>
                                                           </form>

                                                           <form method="POST" action="{{ route($routePath . '.restore', ['section' => $section]) }}">
                                                               @csrf
                                                               <button class="flex w-full items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700">
                                                                   {{ __('personalization.sections.restore') }}
                                                               </button>
                                                           </form>
                                                           <form method="POST" action="{{ route($routePath . '.switch', ['section' => $section]) }}">
                                                               @csrf
                                                           <button class="flex w-full items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700">
                                                               {{ $section->is_active ? __('personalization.sections.disable') : __('personalization.sections.enable') }}
                                                           </button>
                                                           </form>
                                                           <form method="POST" action="{{ route($routePath . '.destroy', ['section' => $section]) }}">
                                                               @method('DELETE')
                                                               @csrf
                                                           <button class="flex items-center w-full gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700">
                                                               {{ __('global.delete') }}
                                                           </button>
                                                           </form>

                                                       </div>
                                                   </div>
                                                   @if ($section->isPremium())
                                                       <button type="button" class="absolute top-3 right-3 z-10 btn btn-sm">
                                                           <i class="bi bi-star text-warning text-lg"></i>
                                                       </button>
                                                    @endif
                                                   @if (!$section->is_active)
                                                       <button type="button" class="absolute top-3 right-3 z-10 btn btn-sm">
                                                           <i class="bi bi-eye-slash text-danger text-lg"></i>
                                                       </button>
                                                    @endif
                                                   <div class="container custom-scroll">
                                                       {!! $section->toDTO()->render() !!}
                                                   </div>
                                               </div>
                                           </li>
                                       @endforeach
                                   </ul>

                        </div>
                    </div>

                                <div class="card">
                                    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 hidden sm:block mb-4">
                                        {{ __('personalization.sections.types.title', ['name' => app('theme')->getTheme()->name]) }}
                                    </h3>
                                    @foreach ($sectionTypes as $sectionType)
                                            <ul class="w-full" >
                                                @foreach ($sectionType->sections as $section)
                                                    <li class="flex items-center justify-between py-2 px-4 dark:border-neutral-700 rounded-lg mb-2">
                                                        <div class="group relative flex flex-col h-full bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700 dark:shadow-slate-700/70">
                                                            <div class="hs-dropdown absolute top-3 left-3 z-10">
                                                                <form method="POST" action="{{ route($routePath . '.clone_section', ['section' => $section->uuid]) }}?active_page={{ $uuid }}">
                                                                    @csrf
                                                                    <button class="flex justify-center items-center size-9 text-sm font-semibold rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 dark:focus:bg-neutral-800 p-2" type="submit">
                                                                        <i class="bi bi-cloud-plus"></i>
                                                                    </button>
                                                                </form>

                                                            </div>
                                                            <img src="{{ $section->thumbnail() }}" class="rounded-b-xl w-full object-cover">
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                    @endforeach
                            </div>
                    </div>

                    @endif

    @else
        <div class="min-h-60 flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                <svg class="size-10 text-gray-500 dark:text-neutral-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" x2="2" y1="12" y2="12"></line>
                    <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
                    <line x1="6" x2="6.01" y1="16" y2="16"></line>
                    <line x1="10" x2="10.01" y1="16" y2="16"></line>
                </svg>
                <p class="mt-2 text-sm text-gray-800 dark:text-neutral-300">
                    No sections found
                </p>
            </div>
        </div>
        @endif
    </div>
@endsection
