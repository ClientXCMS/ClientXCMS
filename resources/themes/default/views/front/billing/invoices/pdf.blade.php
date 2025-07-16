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
    <!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('client.invoices.details') }} - {{ $invoice->identifier() }}</title>

    <style>
        * {
            font-family: Verdana, Arial, sans-serif;
        }
        table{
            font-size: x-small;
        }
        .bordered {
            border: 1px solid {{ $primaryColor }};
            padding: 10px;

        }
        .bordered-sm {
            border: 1px solid {{ $primaryColor }};
            padding: 5px;
            border-radius: 5%;
        }
        tfoot tr td{
            font-weight: bold;
            font-size: x-small;
        }

        .gray {
            background-color: {{ $primaryColor }};
            color: {{ $color }};
            border-radius: 5%;
        }
        .thead {
            background-color: {{ $primaryColor }};
            color: {{ $color }};
        }
        .thead th {
            padding: 10px;
        }
        .table-sm .thead th {
            padding: 10px;
            text-align: left;
            margin-top: 40px;
        }
        .table-sm .bordered-sm {
            padding: 5px;
            border: none;
        }
        h2 {
            margin-bottom: 0;
        }
        body {
            font-size: 14px;

            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            overflow: hidden;
        }
        .invoice-badge {
            padding: 5px;
            border-radius: 5px;
            color: white;
        }
        .invoice-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .invoice-pending, .invoice-draft {
            background-color: #fff3cd;
            color: #856404;
        }
        .invoice-refunded, .invoice-cancelled {
            background-color: #cce5ff;
            color: #004085;
        }
        .invoice-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .detail-label {
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .invoice-alert {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 1em;
            text-align: center;
            position: absolute;
            top: 10%;
            left: 80%;
            z-index: 10;
        }
    </style>
</head>
<body>

<div class="invoice-alert invoice-{{ $invoice->status }}">
    {{ __('global.states.' . $invoice->status) }}
</div>
<table>
    <tr>
        <td style="display: block;">
            <img src="{{ $logoSrc }}" style="max-width: 200px; max-height: 100px;" alt="{{ setting('app.name') }}">
        </td>
        <td>
            <h1 style="text-align: left;">
                {{ __('global.invoice') }} # {{ $invoice->identifier() }}
            </h1>
        </td>
    </tr>
    <tr>

        <td>
            <h3>{{ __('client.invoices.details') }}</h3>
            <span><span class="detail-label">{{ __('client.invoices.invoice_date') }}</span>: {{ $invoice->created_at->format('d/m/Y') }}<br/><span class="detail-label">{{ __('client.invoices.due_date') }}</span>: {{ $invoice->due_date->format('d/m/Y') }}<br><span class="detail-label">{{ __('client.invoices.paymethod') }}</span>: {{ $invoice->gateway != null ? $invoice->gateway->name : $invoice->paymethod }}<br/><br/>
            </span>
        </td>
    </tr>
    <tr>
        <td style="display: block;">
            <h3>{{ setting('app.name') }}</h3>
            <pre>{!! setting('app.address') !!}</pre>
        </td>
        <td>
            <h3>{{ __('client.invoices.billto', ['name' => $customer->firstname . ' ' . $customer->lastname]) }}</h3>
            <pre>{{ $customer->email }}<br>{{ $customer->address }} {{ $customer->address2 != null ? ',' . $customer->address2 : '' }}<br>{{ $customer->region }}, {{ $customer->city }} , {{ $customer->zipcode }}<br/>{{ $countries[$customer->country] }}<br>
            </pre>
        </td>
    </tr>

</table>
<br/>

<table width="100%" class="table" style="margin-bottom: 50px;">
    <thead class="thead">
    <tr>
        <th class="bordered">#</th>
        <th class="bordered">{{ __('client.invoices.itemname') }}</th>
        <th class="bordered">{{ __('client.invoices.qty') }}</th>
        <th class="bordered">{{ __('store.unit_price') }}</th>
        <th class="bordered">{{ __('store.setup_price') }}</th>
        <th class="bordered" >{{ __('store.price') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($invoice->items as $item)

        <tr>
            <th scope="row" class="bordered">{{ $item->id }}</th>
            <td class="bordered">{{ $item->name }}
                @if($item->canDisplayDescription())
                    <br/><small>{{ $item->description }}</small>
                @endif
                <br/>
                @if ($item->getDiscount(false))
                    <small>{{ $item->getDiscountLabel() }}</small>
                @endif
            </td>
            <td class="bordered text-right">{{ $item->quantity }}</td>
            <td class="bordered text-right">{{ formatted_price($item->unit_price_ht, $invoice->currency) }}
                @if ($item->getDiscount() != null && $item->getDiscount(true)->sub_price != 0)
                    <br/><small>-{{ formatted_price($item->getDiscount()->sub_price, $invoice->currency) }}</small>
                @endif
            </td>
            <td class="bordered text-right">{{ formatted_price($item->unit_setup_ht, $invoice->currency) }}
                @if ($item->getDiscount() != null && $item->getDiscount(true)->sub_setup != 0)

                    <br/><small>-{{ formatted_price($item->getDiscount()->sub_setup, $invoice->currency) }}</small>
                @endif
            </td>
            <td class="bordered text-right">{{ formatted_price($item->price(), $invoice->currency) }}
                @if ($item->getDiscount() != null && $item->getDiscount(true)->sub_price != 0 || $item->getDiscount() != null && $item->getDiscount(true)->sub_setup != 0)
                    <br/><small>-{{ formatted_price($item->getDiscount()->sub_price + $item->getDiscount()->sub_setup, $invoice->currency) }}</small>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>

    <tfoot>
    @if ($invoice->getDiscountTotal() > 0)
        <tr>
            <td colspan="4"></td>
            <td class="text-right">{{ __('coupon.coupon') }}</td>
            <td class="bordered-sm text-right">-{{ formatted_price($invoice->getDiscountTotal(), $invoice->currency) }}</td>
        </tr>
    @endif
    <tr>
        <td colspan="4"></td>
        <td class="text-right">{{ __('store.subtotal') }}</td>
        <td class="bordered-sm text-right">{{ formatted_price($invoice->subtotal, $invoice->currency) }}</td>
    </tr>
    <tr>
        <td colspan="4"></td>
        <td class="text-right">{{ __('store.vat') }}</td>
        <td class="bordered-sm text-right">{{ formatted_price($invoice->tax, $invoice->currency) }}</td>
    </tr>
    <tr>
        <td colspan="4"></td>
        <td class="text-right">{{ __('store.total') }}</td>
        <td class="bordered-sm text-right">{{ formatted_price($invoice->total, $invoice->currency) }}</td>
    </tr>
    </tfoot>
</table>
@if ($invoice->external_id != null)

    <table width="100%" class="table-sm">
        <thead class="thead">
        <tr>
            <th class="bordered">{{ __('client.invoices.paymethod') }}</th>
            <th class="bordered">{{ __('client.invoices.paid_date') }}</th>
            <th class="bordered">{{ __('admin.invoices.show.external_id') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="bordered">
                {{ $invoice->gateway != null ? $invoice->gateway->name : $invoice->paymethod }}
            </td>
            <td class="bordered">{{ $invoice->paid_at ? $invoice->paid_at->format('d/m/y H:i') : 'N/A' }}</td>
            <td class="bordered">{{ $invoice->external_id }}</td>
        </tr>
        </tbody>
    </table>
@endif
<table width="100%">
    <tr>
        <td>
            @if ($invoice->paymethod == 'bank_transfert' && $invoice->status != 'paid')
                <h3>{{ __('client.invoices.banktransfer.title') }}</h3>
                <span>
                    {!! nl2br(setting("bank_transfert_details", "You can change this details in Bank transfer configuration.")) !!}
                </span>
            @elseif ($invoice->status == 'paid')
                <h3>{{ __('client.invoices.thank') }}</h3>
                <span>
                    {{ __('client.invoices.thankmessage') }}
                </span>
            @endif
        </td>
    </tr>
</table>
<table width="100%">
    @if (!empty(setting("invoice_terms")))
        <tr>
            <td>
                <h3>{{ __('client.invoices.terms') }}</h3>
                <span>
                    {!! nl2br(setting("invoice_terms", "You can change this details in Invoice configuration.")) !!}
                </span>
            </td>
        </tr>
    @endif
    <tr>
        <td>
            <h3>{{ date('Y') }} {{ config('app.name') }}.</h3>
        </td>
    </tr>
</table>
</body>
</html>
