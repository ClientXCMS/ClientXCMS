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

@extends('layouts/front')
@section('title', $title)
@section('content')

    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <div class="max-w-2xl mx-auto text-center mb-10 lg:mb-14">
            <h2 class="text-2xl font-bold md:text-4xl md:leading-tight dark:text-white">{{ $title }}</h2>
            <p class="mt-1 text-gray-600 dark:text-gray-400">{{ $subtitle }}</p>
        </div>
        @include("shared.alerts")

        @foreach($groups->chunk(3) as $row)

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-4">

                @foreach($row as $group)
                    @php($startPrice = $group->startPrice())
                    <div class="group flex flex-col h-full bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-700 dark:shadow-slate-700/[.7]">
                        @if ($group->image)
                            <div class="h-52 flex flex-col justify-center items-center bg-indigo-600 rounded-t-xl">
                                <img src="{{ Storage::url($group->image) }}" class="{{ $group->useImageAsBackground() ? 'h-full w-full' : 'h-32 w-32' }}" alt="{{ $group->trans('name') }}">
                            </div>
                        @endif
                        <div class="p-4 md:p-6">
                            @if ($group->pinned)
                                <span class="block mb-1 text-xs font-semibold uppercase text-blue-600 dark:text-blue-500">
                                    {{ $group->getMetadata('pinned_label', __('store.pinned')) }}
                                </span>
                            @endif
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 dark:hover:text-white">
                                {{ $group->trans('name') }}
                            </h3>
                            <p class="mt-3 text-gray-500">
                                {{ $group->trans('description') }}
                            </p>
                        </div>
                        <div class="mt-auto flex border-t border-gray-200 divide-x divide-gray-200 dark:border-gray-700 dark:divide-gray-700">
                            <a href="{{ $group->route() }}" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-es-xl bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                @if ($startPrice->isFree())
                                    {{ __('global.free') }}
                                @else
                                    {{ __('store.from_price', ['price' => $startPrice->price, 'currency' => $startPrice->currency]) }}
                                @endif
                            </a>
                            <a href="{{ $group->route() }}" class="w-full py-3 px-4 inline-flex justify-center text-primary items-center gap-x-2 text-sm font-medium rounded-ee-xl bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                {{ __('global.seemore') }} <span class="sr-only">{{ $group->name }}</span>
                                @include("shared.icons.array-right")
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
        @foreach($products->chunk(3) as $row)

            <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:items-center">

                @foreach($row as $product)
                    @if($product->pinned)
                        @include('shared.products.pinned')
                    @else
                        @include('shared.products.product')
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
@endsection
