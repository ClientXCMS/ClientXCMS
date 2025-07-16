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
@section('title', __('extensions.settings.title'))
@section('setting')
    <div class="card">
        <div class="card-heading">
            <div>
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('extensions.settings.title') }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
            {{ __('extensions.settings.description') }}
        </p>
            </div>
            <div class="flex">

                <form action="{{ route('admin.settings.extensions.clear') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <button type="submit" class="btn btn-warning mt-3">{{ __('extensions.settings.clearcache') }}</button>
                </form>
            </div>
            </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Card -->
            @foreach ($extensions as $extension)

            <div class="group flex flex-col h-full bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-slate-900 dark:border-slate-700 dark:shadow-slate-700/70">
                <div class="h-52 flex flex-col justify-center items-center rounded-t-xl bg-primary">
                    <img src="{{ $extension->thumbnail() }}" style="max-width: 100%; max-height: 75%">
                </div>
                <div class="p-4 md:p-6">
                    <div class="flex justify-between">
        <span class="mb-1 text-xs font-semibold uppercase text-blue-600 dark:text-blue-500 grid content-center">
          {{ $extension->type }}
        </span>
                        <div class="mb-1">
                        @if ($extension->isActivable())
                            @if ($extension->isEnabled())
                                    <span class="inline-flex items-center gap-x-0.5 py-0.5 px-3 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500">{{ __('global.enabled') }}</span>
                                @else
                            <span class="inline-flex items-center gap-x-0.5 py-0.5 px-3 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500">{{ __('extensions.settings.activable') }}</span>
                                @endif
                            @endif
                        </div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-slate-300">
                        {{ $extension->name() }}
                    </h3>
                    @if(!$extension->isActivable())
                        <span class="inline-flex items-center gap-x-0.5 py-0.5 px-3 rounded-full text-xs font-semibold bg-blue-300 text-blue-800 dark:bg-blue-800/30 dark:text-blue-500">@foreach ($extension->prices() as $price) @if ($price['billing'] != 'included'){{ $price['price'] }} € {{ __('extensions.'. $price['billing']) }} @else {{ __('extensions.settings.activable') }}  @endif @endforeach</span>
                    @endif
                        <p class="mt-3 text-gray-500 dark:text-slate-500">
                        {{ $extension->description() }}
                    </p>
                </div>

                    @if ($extension->isNotEnabled())
                        @if ($extension->isActivable())

                            <form action="{{ route('admin.settings.extensions.enable', [$extension->type, $extension->uuid]) }}" method="POST" enctype="multipart/form-data" class="mt-auto flex border-t border-gray-200 divide-x divide-gray-200 dark:border-slate-700 dark:divide-slate-700">
                                @csrf
                                <a class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-es-xl bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-white dark:hover:bg-slate-800 dark:focus:bg-slate-800" href="https://clientxcms.com/marketplace/{{ $extension->uuid }}">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                    {{ __('global.seemore') }}
                                </a>
                                <button type="submit" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-ee-xl bg-green-100 shadow-sm hover:bg-green-50 focus:outline-none focus:bg-green-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-white dark:hover:bg-slate-800 dark:focus:bg-slate-800">
                                    <i class="bi bi-arrow-repeat"></i>
                                    {{ __('extensions.settings.enable') }}
                                </button>
                            </form>
                        @else
                        <div class="mt-auto flex border-t border-gray-200 divide-x divide-gray-200 dark:border-slate-700 dark:divide-slate-700">

                            <a class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-ee-xl bg-indigo-300 text-indigo-800 shadow-sm hover:bg-indigo-50 focus:outline-none focus:bg-indigo-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-white dark:hover:bg-slate-800 dark:focus:bg-slate-800" href="https://clientxcms.com/marketplace/{{ $extension->uuid }}">
                                <i class="bi bi-cart"></i>
                                {{ __('extensions.settings.buy') }}
                            </a>
                        </div>
                        @endif
                    @else
                        <form action="{{ route('admin.settings.extensions.disable', [$extension->type, $extension->uuid]) }}" method="POST" enctype="multipart/form-data" class="mt-auto flex border-t border-gray-200 divide-x divide-gray-200 dark:border-slate-700 dark:divide-slate-700">
                            @csrf
                            <a class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-es-xl bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-white dark:hover:bg-slate-800 dark:focus:bg-slate-800" href="https://clientxcms.com/marketplace/{{ $extension->uuid }}">
                                <i class="bi bi-box-arrow-up-right"></i>
                                {{ __('global.seemore') }}
                            </a>
                            <button type="submit" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-ee-xl bg-red-300 text-red-800 shadow-sm hover:bg-red-50 focus:outline-none focus:bg-red-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-slate-700 dark:text-white dark:hover:bg-slate-800 dark:focus:bg-slate-800">
                                <i class="bi bi-ban"></i>
                                {{ __('extensions.settings.disable') }}
                            </button>
                        </form>
                    @endif
            </div>
            @endforeach
        </div>
        </div>
    </div>

@endsection
