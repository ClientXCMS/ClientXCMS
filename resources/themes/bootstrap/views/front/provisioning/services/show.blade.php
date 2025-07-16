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
@section('title', __('client.services.show'))
@section('content')
    <div class="container py-5">
        <div class="row gx-4">
            <div class="col-md-9">
                @include('shared/alerts')
                {!! $panel_html !!}
            </div>
            <div class="col-md-3">
                <div class="d-grid gap-3">

                    @if ($service->canRenew())
                        @if ($service->isFree())
                            <a href="{{ route('front.services.renew', ['service' => $service, 'gateway' => 'balance']) }}" class="btn btn-primary">
                                <i class="bi bi-credit-card-2-front-fill me-2"></i>
                                {{ __('client.services.renewbtn') }}
                            </a>
                        @else
                            <div class="dropdown">
                                <a href="{{ route('front.services.renewal', ['service' => $service]) }}" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-credit-card-2-front-fill me-2"></i>
                                    {{ __('client.services.managerenew') }}
                                </a>
                                <ul class="dropdown-menu">
                                    @foreach ($gateways as $gateway)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('front.services.renew', ['service' => $service, 'gateway' => $gateway->uuid]) }}">
                                                {{ $gateway->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif

                    @if ($service->canUpgrade())
                        <a href="{{ route('front.services.upgrade', ['service' => $service]) }}" class="btn btn-warning">
                            <i class="bi bi-arrows-angle-expand me-2"></i>
                            {{ __('client.services.upgradeservice') }}
                        </a>
                    @endif

                    <a href="{{ route('front.services.options', ['service' => $service]) }}" class="btn btn-secondary">
                        <i class="bi bi-boxes me-2"></i>
                        {{ __('client.services.manageoptions') }}
                    </a>

                    @if (auth('admin')->check())
                        <a href="{{ route('admin.services.show', ['service' => $service]) }}" class="btn btn-outline-primary">
                            <i class="bi bi-box me-2"></i>
                            {{ __('client.services.manageserviceonadmin') }}
                        </a>
                    @endif

                    @if ($service->canCancel())
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="bi bi-x-octagon-fill me-2"></i>
                            {{ __('client.services.cancel.index') }}
                        </button>
                        <!-- Cancel Modal -->
                        <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="cancelModalLabel">{{ __('client.services.cancel.index') }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="{{ route('front.services.cancel', ['service' => $service]) }}" method="post">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="reason" class="form-label">{{ __('client.services.cancel.reason') }}</label>
                                                @include('shared/select', ['name' => 'reason', 'options' => \App\Models\Provisioning\CancellationReason::getReasons(), 'value' => old('reason')])
                                            </div>
                                            <div class="mb-3">
                                                <label for="message" class="form-label">{{ __('client.services.cancel.message') }}</label>
                                                @include('shared/textarea', ['name' => 'message', 'value' => old('message')])
                                            </div>
                                            @if (!$service->isOnetime())
                                                <div class="mb-3">
                                                    <label for="expiration" class="form-label">{{ __('client.services.cancel.expiration') }}</label>
                                                    @include('shared/select', ['name' => 'expiration', 'options' => \App\Models\Provisioning\CancellationReason::getCancellationMode(), 'value' => old('expiration')])
                                                </div>
                                            @endif
                                            <div class="d-flex justify-content-end">
                                                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">{{ __('client.services.cancel.back') }}</button>
                                                <button type="submit" class="btn btn-danger">{{ __('client.services.cancel.index') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($service->canUncancel())
                        <form action="{{ route('front.services.cancel', ['service' => $service]) }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg me-2"></i>
                                {{ __('client.services.cancel.uncancel') }}
                            </button>
                        </form>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ $service->name }}</h5>
                            @if (!empty($service->description))
                                <p class="card-text">{{ $service->description }}</p>
                            @endif
                            <x-badge-state state="{{ $service->status }}" />
                        </div>
                    </div>

                    @if ($service->expires_at)
                        <div class="card mt-3">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted">{{ __('client.services.expire_date') }}</h6>
                                <h5 class="card-title">
                                    <x-service-days-remaining expires_at="{{ $service->expires_at }}" state="{{ $service->status }}"></x-service-days-remaining>

                                </h5>
                            </div>
                        </div>
                    @endif

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted">{{ __('store.price') }}</h6>
                            <h5 class="card-title">
                                {{ formatted_price($service->getBillingPrice()->price, $service->currency) }}
                                <span class="text-muted">/{{ $service->recurring()['unit'] }}</span>
                            </h5>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
