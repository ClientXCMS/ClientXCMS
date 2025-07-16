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
$pricing = $product->getPriceByCurrency(currency(), $billing ?? null);
$showSetup = $pricing->hasSetup() && (isset($showSetup) ? $showSetup : true);
?>

<div class="col-12 col-md-4 col-lg-4">

    <div class="card mb-5 mb-xl-0">
        <div class="card-body p-5">
            <div class="small text-uppercase fw-bold text-muted">{{ $product->trans('name') }} @if ($product->pinned) <i class="bi bi-star-fill text-warning"></i> @endif</div>
            <div class="mb-3">
                <span class="display-4 fw-bold">{{ $pricing->getPriceByDisplayMode() }}</span>
                <span class="text-muted">{{ $pricing->getSymbol() }} {{ $pricing->taxTitle() }}</span>
            </div>
            <p class="text-muted fs-sm">
                {{ $pricing->pricingMessage() }}
            </p>
            <ul class="list-unstyled mb-4">
                {!! $product->trans('description') !!}
            </ul>
            <div class="d-grid">
                @if ($product->isOutOfStock())
                    <a href="#" class="btn btn-secondary disabled">{{ __('store.product.outofstock') }}</a>
                @else
                    <a href="{{ $basket_url ?? $product->basket_url() }}" class="btn btn-primary">{{ $basket_title ?? $product->basket_title() }}</a>
                @endif
            </div>
        </div>
    </div>
</div>
