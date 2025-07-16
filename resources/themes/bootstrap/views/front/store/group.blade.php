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
@extends('layouts/front')
@section('title', $title)
@section('content')

    <div class="container px-4 py-5 py-lg-7 mx-auto">
        <div class="text-center mb-4 mb-lg-5 mx-auto">
            <h2 class="h2 fw-bold">{{ $title }}</h2>
            <p class="mt-2 text-muted">{{ $subtitle }}</p>
        </div>
        @include("shared.alerts")

        @foreach($groups->chunk(3) as $row)

            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">

                @foreach($row as $group)
                    @php($startPrice = $group->startPrice())
                    <div class="col d-flex align-items-stretch">
                        <div class="card mt-3 flex-grow-1">
                            @if ($group->image)
                                <img src="{{ Storage::url($group->image) }}" class="mx-auto {{ $group->useImageAsBackground() ? 'w-100 h-100' : 'w-32 h-32 my-2' }}" @if (!$group->useImageAsBackground()) style="max-height: 100px; max-width: 100%;" @endif alt="{{ $group->trans('name') }}">
                            @endif
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">{{ $group->trans('name') }}

                                    @if ($group->pinned)
                                        <span class="btn btn-outline-primary fs-6">{{ __('store.pinned') }}</span>
                                    @endif
                                </h5>
                                <p class="card-text">{{ $group->trans('description') }}</p>
                                <div class="d-flex justify-content-between align-items-center text-body-secondary mt-auto flex">
                                    <a href="{{ $group->route() }}" class="btn btn-outline-primary">{{ __('global.seemore') }}
                                        <i class="bi bi-chevron-right"></i>

                                    </a>
                                    <small>
                                        @if ($startPrice->isFree())
                                            {{ __('global.free') }}
                                        @else
                                            {{ __('store.from_price', ['price' => $startPrice->price, 'currency' => $startPrice->currency]) }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
        @foreach($products->chunk(3) as $row)

            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
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
