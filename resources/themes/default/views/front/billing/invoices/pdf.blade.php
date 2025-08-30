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

    <!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('client.invoices.details') }} - {{ $invoice->identifier() }}</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Verdana, Arial, sans-serif;
        }

        body {
            font-size: 12px;
            color: #333;
            background-color: #fff;
            margin: 0;
            padding: 40px;
        }

        .invoice-container {
            width: 100%;
            max-width: 800px;
            margin: auto;
        }

        /* --- Header --- */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid {{ $primaryColor }};
        }
        .invoice-header .logo {
            max-width: 180px;
            max-height: 90px;
        }
        .invoice-header .invoice-title-section {
            text-align: right;
        }
        .invoice-header h1 {
            margin: 0;
            color: {{ $primaryColor }};
            font-size: 28px;
            font-weight: bold;
        }
        .invoice-header .invoice-id {
            margin: 0;
            font-size: 14px;
            color: #555;
        }

        /* --- Status Badge --- */
        .invoice-status {
            text-align: right;
            margin-bottom: 20px;
        }
        .invoice-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .invoice-paid { background-color: #d4edda; color: #155724; }
        .invoice-pending, .invoice-draft { background-color: #fff3cd; color: #856404; }
        .invoice-refunded, .invoice-cancelled { background-color: #cce5ff; color: #004085; }
        .invoice-failed { background-color: #f8d7da; color: #721c24; }

        /* --- Addresses & Details (Table Layout) --- */
        .addresses-table {
            width: 100%;
            margin-bottom: 40px;
            border-collapse: collapse;
        }
        .addresses-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px 0 0;
        }
        .addresses-table td:last-child {
            padding-right: 0;
            padding-left: 10px;
        }
        .addresses-table h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: bold;
            color: #000;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .addresses-table pre {
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: Verdana, Arial, sans-serif; /* Ensure pre uses the same font */
        }
        .detail-label {
            font-weight: bold;
            color: #444;
        }

        /* --- Items Table --- */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table thead th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            color: #555;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .items-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .item-name small {
            color: #666;
            font-size: 11px;
        }

        /* --- Totals in Table Footer --- */
        .items-table tfoot td {
            padding: 8px 12px;
            text-align: right;
            border-top: 1px solid #eee;
        }
        .items-table tfoot .label {
            font-weight: bold;
            color: #444;
        }
        .items-table tfoot .grand-total td {
            font-size: 14px;
            font-weight: bold;
            color: {{ $primaryColor }};
            border-top: 2px solid #333;
        }

        /* --- Footer --- */
        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 11px;
            color: #777;
        }
        .footer-section {
            margin-bottom: 20px;
        }
        .footer-section h3 {
            font-size: 13px;
            color: #333;
            margin: 0 0 8px 0;
            font-weight: bold;
        }
        .footer-section span, .footer-section pre {
            line-height: 1.5;
            margin: 0;
        }
        .copyright {
            text-align: center;
            margin-top: 30px;
        }

    </style>
</head>
<body>

<div class="invoice-container">

    <div class="invoice-status">
        <span class="invoice-badge invoice-{{ $invoice->status }}">{{ __('global.states.' . $invoice->status) }}</span>
    </div>

    <header class="invoice-header">
        <div>
            <img src="{{ $logoSrc }}" class="logo" alt="{{ setting('app.name') }}">
        </div>
        <div class="invoice-title-section">
            <h1>{{ __('global.invoice') }}</h1>
            <p class="invoice-id">#{{ $invoice->identifier() }}</p>
        </div>
    </header>

    <table class="addresses-table">
        <tr>
            <td>
                <h3>{{ setting('app.name') }}</h3>
                <pre>{!! setting('app.address') !!}</pre>
            </td>
            <td>
                <h3>{{ __('client.invoices.billto', ['name' => $address[0]]) }}</h3>
                    @foreach ($address as $i => $line)
                        @if ($i == 0)
                            @continue
                        @endif
                        {{ $line }}<br/>
                    @endforeach
            </td>
        </tr>
    </table>

    <section class="invoice-details-section" style="margin-bottom: 40px;">
        <table style="width: 100%;">
            <tr>
                <td><span class="detail-label">{{ __('client.invoices.invoice_date') }}:</span> {{ $invoice->created_at->format('d/m/Y') }}</td>
                <td><span class="detail-label">{{ __('client.invoices.due_date') }}:</span> {{ $invoice->due_date->format('d/m/Y') }}</td>
                <td><span class="detail-label">{{ __('client.invoices.paymethod') }}:</span> {{ $invoice->gateway != null ? $invoice->gateway->name : $invoice->paymethod }}</td>
            </tr>
        </table>
    </section>

    <section class="items-section">
        <table class="items-table">
            <thead>
            <tr>
                <th>{{ __('client.invoices.itemname') }}</th>
                <th class="text-right">{{ __('client.invoices.qty') }}</th>
                <th class="text-right">{{ __('store.unit_price') }}</th>
                <th class="text-right">{{ __('store.setup_price') }}</th>
                <th class="text-right">{{ __('store.price') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td class="item-name">
                        {{ $item->name }}
                        @if($item->canDisplayDescription())
                            <br/><small>{{ $item->description }}</small>
                        @endif
                        @if ($item->getDiscount(false))
                            <br/><small style="color: #c00;">{{ $item->getDiscountLabel() }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">
                        {{ formatted_price($item->unit_price_ht, $invoice->currency) }}
                        @if ($item->getDiscount() != null && $item->getDiscount(true)->sub_price != 0)
                            <br/><small style="color: #c00;">-{{ formatted_price($item->getDiscount()->sub_price, $invoice->currency) }}</small>
                        @endif
                    </td>
                    <td class="text-right">
                        {{ formatted_price($item->unit_setup_ht, $invoice->currency) }}
                        @if ($item->getDiscount() != null && $item->getDiscount(true)->sub_setup != 0)
                            <br/><small style="color: #c00;">-{{ formatted_price($item->getDiscount()->sub_setup, $invoice->currency) }}</small>
                        @endif
                    </td>
                    <td class="text-right">
                        {{ formatted_price($item->price(), $invoice->currency) }}
                        @if ($item->getDiscount() != null && ($item->getDiscount(true)->sub_price != 0 || $item->getDiscount(true)->sub_setup != 0))
                            <br/><small style="color: #c00;">-{{ formatted_price($item->getDiscount()->sub_price + $item->getDiscount()->sub_setup, $invoice->currency) }}</small>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            @if ($invoice->getDiscountTotal() > 0)
                <tr>
                    <td colspan="4" class="label">{{ __('coupon.coupon') }}</td>
                    <td class="text-right">-{{ formatted_price($invoice->getDiscountTotal(), $invoice->currency) }}</td>
                </tr>
            @endif
            <tr>
                <td colspan="4" class="label">{{ __('store.subtotal') }}</td>
                <td class="text-right">{{ formatted_price($invoice->subtotal, $invoice->currency) }}</td>
            </tr>
            @if ($invoice->balance)
                <tr>
                    <td colspan="4" class="label">{{ __('client.invoices.balance.title') }}</td>
                    <td class="text-right">{{ formatted_price($invoice->balance, $invoice->currency) }}</td>
                </tr>
            @endif
            <tr>
                <td colspan="4" class="label">{{ __('store.vat') }}</td>
                <td class="text-right">{{ formatted_price($invoice->tax, $invoice->currency) }}</td>
            </tr>
            <tr class="grand-total">
                <td colspan="4" class="label">{{ __('store.total') }}</td>
                <td class="text-right">{{ formatted_price($invoice->total, $invoice->currency) }}</td>
            </tr>
            </tfoot>
        </table>
    </section>

    <footer class="invoice-footer">
        @if ($invoice->paymethod == 'bank_transfert' && $invoice->status != 'paid')
            <div class="footer-section">
                <h3>{{ __('client.invoices.banktransfer.title') }}</h3>
                <span>{!! nl2br(setting("bank_transfert_details", "You can change this details in Bank transfer configuration.")) !!}</span>
            </div>
        @elseif ($invoice->status == 'paid')
            <div class="footer-section">
                <h3>{{ __('client.invoices.thank') }}</h3>
                <span>{{ __('client.invoices.thankmessage') }}</span>
            </div>
        @endif

        @if ($invoice->external_id != null)
            <div class="footer-section">
                <h3>{{ __('client.invoices.details') }}</h3>
                <span><span class="detail-label">{{ __('client.invoices.paid_date') }}:</span> {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/y H:i') : 'N/A' }} | <span class="detail-label">{{ __('admin.invoices.show.external_id') }}:</span> {{ $invoice->external_id }}</span>
            </div>
        @endif

        @if (!empty(setting("invoice_terms")))
            <div class="footer-section">
                <h3>{{ __('client.invoices.terms') }}</h3>
                <span>{!! nl2br(setting("invoice_terms", "You can change this details in Invoice configuration.")) !!}</span>
            </div>
        @endif

        <div class="copyright">
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </footer>

</div>

</body>
</html>
