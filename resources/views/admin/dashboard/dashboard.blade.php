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
@section('title', 'Dashboard')
@extends('admin.layouts.admin')
@section('content')
    <div class="container mx-auto">
        <div class="card">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __($good_message, ['name' => auth('admin')->user()->firstname])  }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
            {{ trans('admin.dashboard.welcome', ['time' => date('H:i'), 'healthcheck' => empty($healthcheck)]) }}
        </p>
            @foreach ($healthcheck as $type => $messages)
                <div class="mt-2 text-{{ $type }}-500 dark:text-{{ $type }}-500" role="alert" tabindex="-1">
                @foreach($messages as $message)
                    <div>
                        <span>{{ $message }}</span>
                    </div>
                @endforeach
                </div>

                @endforeach
        </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        @foreach ($widgets as $widget)

            <div class="flex flex-col shadow-sm rounded-xl dark:bg-gray-800 bg-gray-100">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg  dark:bg-slate-900 dark:border-gray-800">
                        <i class="{{ $widget->icon }} text-black dark:text-white"></i>
                    </div>

                    <div class="grow">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500">
                                {{ __($widget->title) }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-1 flex items-center gap-x-2">
                        <h3 class="{{ $widget->small ? 'text-sm sm:text-2sm' : 'text-xl sm:text-2xl' }} font-medium text-gray-800 dark:text-gray-200">
                            {{ $widget->value() }}
                        </h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="grid grid-cols-4 gap-4 mt-8">
        @foreach($cards as $card)
            <div class="card-sm col-span-4 {{ $card->cols != 1 ? 'sm:col-span-' . $card->cols : 'sm:col-span-1' }}">
                {!! $card->render() !!}
            </div>
        @endforeach
    </div>
    </div>
@endsection
