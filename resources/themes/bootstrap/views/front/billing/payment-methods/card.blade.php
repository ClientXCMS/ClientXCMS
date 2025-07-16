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
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h5 mb-1">{{ __('client.payment-methods.index') }}</h2>
            <p class="text-muted small mb-0">{{ __('client.payment-methods.index_description') }}</p>
        </div>
        <div>
            @if(isset($count) && $count > 3)
                <a href="{{ route('front.invoices.index') }}" class="btn btn-link text-primary p-0">
                    {{ __('global.seemore') }}
                    <i class="bi bi-chevron-right"></i>
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>{{ __('client.payment-methods.card') }}</th>
                    <th>{{ __('client.payment-methods.expiration') }}</th>
                    <th>{{ __('client.payment-methods.default') }}</th>
                    <th>{{ __('global.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @if (count($sources) == 0)
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            {{ __('global.no_results') }}
                        </td>
                    </tr>
                @endif
                @foreach($sources as $i => $source)
                    <tr>
                        <td>•••• {{ $source->last4 }}</td>
                        <td>{{ $source->exp_month . '/' . $source->exp_year }}</td>
                        <td>
                            @if ($source->isDefault())
                                <span class="badge bg-success">
                                <i class="bi bi-check-circle-fill"></i> {{ __('global.yes') }}
                            </span>
                            @else
                                <span class="badge bg-danger">
                                <i class="bi bi-x-circle-fill"></i> {{ __('global.no') }}
                            </span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex">
                                @if (!$source->isDefault())
                                    <form action="{{ route('front.payment-methods.default', ['paymentMethod' => $source->id]) }}" method="POST" class="me-2">
                                        @csrf
                                        <button class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-sliders2-vertical"></i> {{ __('client.payment-methods.set_default') }}
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('front.payment-methods.delete', ['paymentMethod' => $source->id]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash"></i> {{ __('global.delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
