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
                <h2 class="h5 mb-1">{{ __('client.invoices.index') }}</h2>
                <p class="text-muted small mb-0">{{ __('client.invoices.index_description') }}</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if(isset($count) && $count > 3)
                    <a href="{{ route('front.invoices.index') }}" class="btn btn-link text-primary p-0">
                        {{ __('global.seemore') }}
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @endif
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-funnel"></i> {{ __('global.filter') }}
                        @if ($filter)
                            <span class="badge bg-primary ms-2">{{ count($invoices) }}</span>
                        @endif
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                        @foreach ($filters as $current)
                            <li>
                                <label class="dropdown-item d-flex align-items-center">
                                    <input type="checkbox" id="filter-invoice-{{ $current }}" value="{{ $current }}" data-redirect="{{ route('front.invoices.index') }}" class="form-check-input me-2" @if ($current == $filter) checked @endif>
                                    {{ __('global.states.' . $current) }}
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
                <th>{{ __('client.invoices.identifier') }}</th>
                <th>{{ __('store.total') }}</th>
                <th>{{ __('global.status') }}</th>
                <th>{{ __('client.invoices.due_date') }}</th>
                <th>{{ __('client.invoices.invoice_date') }}</th>
                <th class="text-end">{{ __('global.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @if (count($invoices) == 0)
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        {{ __('global.no_results') }}
                    </td>
                </tr>
            @endif
            @foreach($invoices as $invoice)
                <tr>
                    <td class="text-primary">{{ $invoice->identifier() }}</td>
                    <td>{{ formatted_price($invoice->total, $invoice->currency) }}</td>
                    <td>
                        <x-badge-state state="{{ $invoice->status }}"></x-badge-state>
                    </td>
                    <td>{{ $invoice->due_date->format('d/m/y') }}</td>
                    <td>{{ $invoice->created_at->format('d/m/y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('front.invoices.show', ['invoice' => $invoice]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye-fill"></i> {{ __('global.view') }}
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    </div>
    @if ($invoices->hasPages())
    <div class="card-footer">
        {{ $invoices->links('shared.layouts.pagination') }}
    </div>
    @endif
</div>
