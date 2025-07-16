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
<div class="card">


    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h5 mb-1">{{ __('client.services.index') }}</h2>
                <p class="text-muted small mb-0">{{ __('client.services.index_description') }}</p>
            </div>
            <div>
                @if(isset($count) && $count > 3)
                    <a href="{{ route('front.services.index') }}" class="btn btn-link text-primary p-0">
                        {{ __('global.seemore') }}
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @endif
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-funnel"></i> {{ __('global.filter') }}
                        @if ($filter)
                            <span class="badge bg-primary ms-2">{{ count($services) }}</span>
                        @endif
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                        @foreach ($filters as $current => $value)
                        <li>
                                <label class="dropdown-item d-flex align-items-center">
                                    <input type="checkbox" id="filter-service-{{ $current }}" value="{{ $current }}" class="form-check-input me-2" data-redirect="{{ route('front.services.index') }}" @if ($current == $filter) checked @endif>
                                    {{ in_array($value,array_keys( __('global.states'))) ? (__('global.states.' . $value)) : $value }}
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>{{ __('global.name') }}</th>
                    <th>{{ __('store.price') }}</th>
                    <th>{{ __('global.status') }}</th>
                    <th>{{ __('client.services.expire_date') }}</th>
                    <th class="text-end">{{ __('global.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @if (count($services) == 0)
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="p-4">
                                @include("shared.icons.shopping-cart")
                                <p class="mt-3 text-muted">{{ __('client.services.noservices') }}</p>
                                <a href="{{ route('front.store.index') }}" class="btn btn-primary btn-sm">
                                    {{ __('client.services.startorder') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @endif

                @foreach($services as $service)
                    <tr>
                        <td>#{{ $service->id }} - {{ $service->excerptName() }}</td>
                        <td>{{ formatted_price($service->getBillingPrice()->price, $service->currency) }}</td>
                        <td>
                            <x-badge-state state="{{ $service->status }}"></x-badge-state>
                        </td>
                        <td>
                            <x-service-days-remaining expires_at="{{ $service->expires_at }}" state="{{ $service->status }}" date_at="{{ $service->status == 'expired' && $service->expire_at != null ? $service->expires_at : ($service->suspended_at != null ? $service->suspended_at : $service->cancelled_at) }}"></x-service-days-remaining>

                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                @if ($service->canManage())
                                    <a href="{{ route('front.services.show', ['service' => $service]) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-gear"></i> {{ __('client.services.managebtn') }}
                                    </a>
                                @endif

                                @if ($service->canRenew() && !isset($count))
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="renewDropdown-{{ $service->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-arrow-repeat"></i> {{ __('client.services.renewbtn') }}
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="renewDropdown-{{ $service->id }}">
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
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if ($services->hasPages())
    <div class="card-footer">
        <div class="d-flex justify-content-center">
            {{ $services->links('shared.layouts.pagination') }}
        </div>
    </div>
    @endif
</div>
