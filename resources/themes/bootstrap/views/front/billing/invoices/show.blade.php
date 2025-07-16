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
/*
                                           * This file is part of the CLIENTXCMS project.
                                           * This file is the property of the CLIENTXCMS association. Any unauthorized use, reproduction, or download is prohibited.
                                           * For more information, please consult our support: clientxcms.com/client/support.
                                           * Year: 2024
                                           */
                                          ?>
                                    @extends('layouts/client')
                                    @section('title', __('client.invoices.details'))
                                    @section('content')
                                        <div class="container py-5">
                                            <div class="row justify-content-center">
                                                <div class="col-lg-10">
                                                    @include('shared/alerts')

                                                    <div class="card">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <img src="{{ setting('app_logo_text') }}" alt="{{ setting('app_name') }}" class="img-fluid" style="max-height: 50px;">
                                                                </div>
                                                                <div class="text-end">
                                                                    <h2 class="fs-4 fw-semibold">{{ __('global.invoice') }} #</h2>
                                                                    <p class="text-muted">{{ $invoice->identifier() }}</p>
                                                                    <address class="mt-3 text-muted">
                                                                        {!! nl2br(setting('app_address')) !!}
                                                                    </address>
                                                                </div>
                                                            </div>

                                                            <div class="row mt-4">
                                                                <div class="col-md-6">
                                                                    <h5 class="fw-semibold">{{ __('client.invoices.billto', ['name' => $customer->firstname . ' ' . $customer->lastname]) }}</h5>
                                                                    <address class="text-muted">
                                                                        {{ $customer->email }}<br>
                                                                        {{ $customer->address }} {{ $customer->address2 ? ', ' . $customer->address2 : '' }}<br>
                                                                        {{ $customer->region }}, {{ $customer->city }}, {{ $customer->zipcode }}<br>
                                                                        {{ $countries[$customer->country] }}
                                                                    </address>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <dl class="row mb-0">
                                                                        <dt class="col-sm-4">{{ __('client.invoices.invoice_date') }}</dt>
                                                                        <dd class="col-sm-8 text-muted">{{ $invoice->created_at->format('d/m/y H:i') }}</dd>
                                                                        <dt class="col-sm-4">{{ __('client.invoices.due_date') }}</dt>
                                                                        <dd class="col-sm-8 text-muted">{{ $invoice->due_date->format('d/m/y H:i') }}</dd>
                                                                        <dt class="col-sm-4">{{ __('global.status') }}</dt>
                                                                        <dd class="col-sm-8">
                                                                            <x-badge-state state="{{ $invoice->status }}"></x-badge-state>
                                                                        </dd>
                                                                    </dl>
                                                                </div>
                                                            </div>

                                                            <div class="table-responsive mt-4">
                                                                <table class="table table-bordered table-hover">
                                                                    <thead class="table-light">
                                                                    <tr>
                                                                        <th>{{ __('client.invoices.itemname') }}</th>
                                                                        <th>{{ __('client.invoices.qty') }}</th>
                                                                        <th>{{ __('store.unit_price') }}</th>
                                                                        <th>{{ __('store.setup_price') }}</th>
                                                                        <th>{{ __('store.price') }}</th>
                                                                    </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                    @foreach ($invoice->items as $item)
                                                                        <tr>
                                                                            <td>
                                                                                <p class="mb-0 fw-semibold">{{ $item->name }}</p>
                                                                                @if ($item->canDisplayDescription())
                                                                                    <p class="text-muted small mb-0">{!! nl2br($item->description) !!}</p>
                                                                                @endif
                                                                                @if ($item->getDiscount(false) != null)
                                                                                    <p class="text-muted small mb-0">{{ $item->getDiscountLabel() }}</p>
                                                                                @endif
                                                                            </td>
                                                                            <td>{{ $item->quantity }}</td>
                                                                            <td>
                                                                                {{ formatted_price($item->unit_price_ht, $invoice->currency) }}
                                                                                @if ($item->getDiscount() && $item->getDiscount(true)->sub_price != 0)
                                                                                    <br><small class="text-secondary-emphasis">-{{ formatted_price($item->getDiscount()->sub_price, $invoice->currency) }}</small>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                {{ formatted_price($item->unit_setup_ht, $invoice->currency) }}
                                                                                @if ($item->getDiscount() && $item->getDiscount(true)->sub_setup != 0)
                                                                                    <br><small class="text-secondary-emphasis">-{{ formatted_price($item->getDiscount()->sub_setup, $invoice->currency) }}</small>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                {{ formatted_price($item->price(), $invoice->currency) }}
                                                                                @if ($item->getDiscount() && $item->getDiscount(true)->sub_price + $item->getDiscount(true)->sub_setup != 0)
                                                                                    <br><small class="text-secondary-emphasis">-{{ formatted_price($item->getDiscount()->sub_price + $item->getDiscount()->sub_setup, $invoice->currency) }}</small>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                    </tbody>
                                                                    <tfoot>
                                                                    <tr>
                                                                        <td colspan="4" class="text-end fw-semibold">{{ __('store.subtotal') }}</td>
                                                                        <td>{{ formatted_price($invoice->subtotal, $invoice->currency) }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td colspan="4" class="text-end fw-semibold">{{ __('store.vat') }}</td>
                                                                        <td>{{ formatted_price($invoice->tax, $invoice->currency) }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td colspan="4" class="text-end fw-bold">{{ __('store.total') }}</td>
                                                                        <td class="fw-bold">{{ formatted_price($invoice->total, $invoice->currency) }}</td>
                                                                    </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </div>


                                                        @if ($invoice->external_id)
                                                                <div class="mt-4">
                                                                    <h5 class="fw-semibold">{{ __('client.invoices.paymethod') }}</h5>
                                                                    <table class="table">
                                                                        <thead class="table-light">
                                                                        <tr>
                                                                            <th>{{ __('client.invoices.paymethod') }}</th>
                                                                            <th>{{ __('client.invoices.paid_date') }}</th>
                                                                            <th>{{ __('admin.invoices.show.external_id') }}</th>
                                                                        </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                        <tr>
                                                                            <td>{{ $invoice->gateway->name ?? $invoice->paymethod }}</td>
                                                                            <td>{{ $invoice->paid_at ? $invoice->paid_at->format('d/m/y H:i') : 'N/A' }}</td>
                                                                            <td>{{ $invoice->external_id }}</td>
                                                                        </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @endif

                                                            <div class="d-flex justify-content-end gap-3 mt-4">
                                                                <a href="{{ route('front.invoices.download', ['invoice' => $invoice]) }}" class="btn btn-outline-secondary">
                                                                    {{ __('client.invoices.download') }}
                                                                </a>
                                                                <button onclick="window.print();" class="btn btn-primary">
                                                                    {{ __('client.invoices.print') }}
                                                                </button>
                                                            </div>

                                                            @if (!empty(setting("invoice_terms")))
                                                                <div class="mt-4">
                                                                    <h6 class="fw-semibold">{{ __('client.invoices.terms') }}</h6>
                                                                    <p class="text-muted">{!! nl2br(setting("invoice_terms", "You can change this details in Invoice configuration.")) !!}</p>
                                                                </div>
                                                            @endif

                                                            @if ($invoice->paymethod == 'bank_transfert' && $invoice->status != 'paid')
                                                                <div class="mt-4">
                                                                    <h6 class="fw-semibold">{{ __('client.invoices.banktransfer.title') }}</h6>
                                                                    <p class="text-muted">{!! nl2br(setting("bank_transfert_details", "You can change this details in Bank transfer configuration.")) !!}</p>
                                                                </div>
                                                            @elseif ($invoice->status == 'paid')
                                                                <div class="mt-4">
                                                                    <h6 class="fw-semibold">{{ __('client.invoices.thank') }}</h6>
                                                                    <p class="text-muted">{{ __('client.invoices.thankmessage') }}</p>
                                                                </div>
                                                            @endif

                                                            <footer class="mt-4 text-center text-muted">
                                                                <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
                                                            </footer>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    @endsection
