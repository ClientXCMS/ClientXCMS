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
@extends('layouts/client')
@section('title', __('client.payment-methods.index'))
@section('content')
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        @include('shared/alerts')
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    @include('front/billing/payment-methods/card', ['sources' => $sources])
                    <div class="grid grid-cols-1 gap-4 mt-6 sm:grid-cols-2">
                        <div class="card">
                            <div class="card-heading">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                        {{ __('client.payment-methods.add') }}
                                    </h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('client.payment-methods.add_description') }}
                                    </p>
                                </div>
                            </div>
                            <div class="card-body">
                                @foreach ($gateways as $gateway)
                                    <form method="POST" action="{{ route('front.payment-methods.add', $gateway->id) }}" id="payment-form-{{ $gateway->uuid }}">
                                        @csrf
                                        {!! $gateway->paymentType()->sourceForm() !!}
                                    </form>
                                @endforeach
                            </div>
                        </div>

                        @if (app('extension')->extensionIsEnabled('fund'))
                            @include('fund::card')
                        @endif
                        @if (app('extension')->extensionIsEnabled('giftcard'))
                            @include('giftcard::card')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
