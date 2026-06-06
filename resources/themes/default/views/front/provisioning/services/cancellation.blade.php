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

@extends('layouts/client')
@section('title', __('client.services.cancel.index'))
@section('content')
    <div class="max-w-[85rem] py-5 lg:py-7 mx-auto">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="md:w-3/4">
                @include('shared/alerts')
                <div class="card">
                    <div class="card-heading">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                {{ __('client.services.cancel.index') }}
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('client.services.cancel.index_description') }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @if (! $allowCancellation)
                            <div class="p-4 rounded-md bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-100">
                                {{ __('client.services.cancel.disabled') }}
                            </div>
                        @elseif ($isLocked && $lockEndsAt)
                            <div class="p-4 rounded-md bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-100">
                                {{ __('client.services.cancel.locked', ['date' => $lockEndsAt->format('d/m/Y H:i')]) }}
                            </div>
                        @endif

                        @if ($activeRequest)
                            <div class="bg-gray-50 dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                            {{ __('client.services.cancel.existing_request') }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('client.services.cancel.status.'.$activeRequest->status) }}
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-200 dark:bg-slate-700 dark:text-gray-100 text-gray-700">
                                        {{ $activeRequest->mode === \App\Models\Provisioning\CancellationRequest::MODE_END_OF_PERIOD ? __('client.services.cancel.expiration_end') : __('client.services.cancel.expiration_now') }}
                                    </span>
                                </div>
                                <dl class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                                    <div>
                                        <dt class="font-semibold">{{ __('global.created') }}</dt>
                                        <dd>{{ $activeRequest->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                                    </div>
                                    @if ($activeRequest->execute_at)
                                        <div>
                                            <dt class="font-semibold">{{ __('client.services.cancel.expiration') }}</dt>
                                            <dd>{{ $activeRequest->execute_at->format('d/m/Y H:i') }}</dd>
                                        </div>
                                    @endif
                                    <div>
                                        <dt class="font-semibold">{{ __('client.services.cancel.reason') }}</dt>
                                        <dd>{{ $activeRequest->reason }}</dd>
                                    </div>
                                    @if ($activeRequest->message)
                                        <div>
                                            <dt class="font-semibold">{{ __('client.services.cancel.message') }}</dt>
                                            <dd>{{ $activeRequest->message }}</dd>
                                        </div>
                                    @endif
                                </dl>

                                @if ($activeRequest->isActionable())
                                    <form action="{{ route('front.services.cancel', ['service' => $service]) }}" method="POST" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="withdraw" value="1">
                                        <button type="submit" class="btn-secondary">
                                            {{ __('client.services.cancel.withdraw') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @php($canSubmit = $allowCancellation && ! $isLocked && $service->canCancel())
                        @if ($canSubmit)
                            <form action="{{ route('front.services.cancel', ['service' => $service]) }}" method="POST" class="space-y-4">
                                @csrf
                                @include('shared/select', ['name' => 'reason', 'label' => __('client.services.cancel.reason'), 'options' => $reasons, 'value' => old('reason')])
                                @include('shared/textarea', ['name' => 'details', 'label' => __('client.services.cancel.message'), 'value' => old('details')])
                                @if (!$service->isOnetime())
                                    @include('shared/select', ['name' => 'expiration', 'label' => __('client.services.cancel.expiration'), 'options' => $modes, 'value' => old('expiration')])
                                @endif
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('front.services.show', ['service' => $service]) }}" class="btn-secondary">
                                        {{ __('client.services.cancel.back') }}
                                    </a>
                                    <button type="submit" class="btn-primary">
                                        {{ __('client.services.cancel.submit') }}
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            <div class="md:w-1/4">
                <div class="card card-sm">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-1">
                        {{ __('client.services.show') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $service->name }}
                    </p>
                    <a href="{{ route('front.services.show', ['service' => $service]) }}" class="btn btn-primary mt-3 w-full">
                        {{ __('client.services.managebtn') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
