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

@extends('admin.layouts.admin')
@section('title', __('admin.settings.title'))
@section('content')
    <div class="container mx-auto">
        @include('admin/shared/alerts')
    <div class="grid gap-2 sm:grid-cols-2 grid-cols-1">
    @foreach($cards as $card)
        <div class="grid-cols-1 sm:col-span-{{ $card->columns }}">
    <div class="py-3 sm:py-6 card flex flex-col h-full">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __($card->name) }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-40">{{ __($card->description) }}</p>

        <div class="grid gap-2 md:grid-cols-{{ $card->columns }} lg:grid-cols-{{ $card->columns + 1 }}">
            <!-- Card -->
            @foreach ($card->items as $item)
            @if ($item->isActive())
            <a class="bg-white p-4 transition duration-300 rounded-lg hover:bg-gray-100 dark:bg-slate-900 dark:border-gray-800 dark:hover:bg-white/[.05] dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="{{ $item->url() }}">
                <div class="flex">
                    <div class="mt-1.5 flex justify-center flex-shrink-0 rounded-s-xl">
                        <i class="w-5 h-5 text-gray-800 dark:text-gray-200 {{ $item->icon }}" style="font-size: 25px"></i>
                    </div>

                    <div class="grow ms-6">
                        <h3 class="text-sm font-semibold text-indigo-600 dark:text-indigo-600">
                            {{ __($item->name) }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-500">
                            {{ __($item->description, ['name' => __($item->name)]) }}
                        </p>
                    </div>
                </div>
            </a>
            @else
            <div class="bg-gray-100 p-4 rounded-lg opacity-50 cursor-not-allowed dark:bg-slate-800" style="pointer-events: none;">
                <div class="flex">
                    <div class="mt-1.5 flex justify-center flex-shrink-0 rounded-s-xl">
                        <i class="w-5 h-5 text-gray-400 dark:text-gray-600 {{ $item->icon }}" style="font-size: 25px"></i>
                    </div>

                    <div class="grow ms-6">
                        <h3 class="text-sm font-semibold text-gray-400 dark:text-gray-600">
                            {{ __($item->name) }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-400 dark:text-gray-600">
                            {{ __($item->description, ['name' => __($item->name)]) }}
                        </p>
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
        </div>
    @endforeach
    </div>

    </div>

@endsection
