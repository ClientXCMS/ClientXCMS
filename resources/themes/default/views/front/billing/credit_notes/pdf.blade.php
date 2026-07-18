<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ __('admin.credit_notes.credit_note') }} - {{ $creditNote->credit_note_number }}</title>
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
            background-color: #d4edda;
            color: #155724;
        }

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
            font-family: Verdana, Arial, sans-serif;
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

        .footer-section span {
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
            <span class="invoice-badge">{{ __('admin.credit_notes.issued') }}</span>
        </div>

        <header class="invoice-header">
            <div>
                <img src="{{ $logoSrc }}" class="logo" alt="{{ setting('app.name') }}">
            </div>
            <div class="invoice-title-section">
                <h1>{{ __('admin.credit_notes.credit_note') }}</h1>
                <p class="invoice-id">#{{ $creditNote->credit_note_number }}</p>
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
                        {{ $line }}<br />
                    @endforeach
                </td>
            </tr>
        </table>

        <section class="invoice-details-section" style="margin-bottom: 40px;">
            <table style="width: 100%;">
                <tr>
                    <td><span class="detail-label">{{ __('admin.credit_notes.date') }}:</span>
                        {{ $creditNote->created_at->format('d/m/Y') }}</td>
                    <td><span class="detail-label">{{ __('admin.credit_notes.original_invoice') }}:</span>
                        #{{ $creditNote->invoice->invoice_number }}</td>
                    <td><span class="detail-label">{{ __('client.invoices.generated_at') }}:</span>
                        {{ now()->format('d/m/Y H:i') }}</td>
                </tr>
            </table>
        </section>

        <section class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>{{ __('client.invoices.itemname') }}</th>
                        <th class="text-right">{{ __('store.price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            {{ __('admin.credit_notes.refund_for_invoice', ['number' => $creditNote->invoice->invoice_number]) }}
                            @if ($creditNote->reason)
                                <br /><small>{{ $creditNote->reason }}</small>
                            @endif
                        </td>
                        <td class="text-right">
                            {{ formatted_price($creditNote->amount, $creditNote->currency) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="label">{{ __('store.subtotal') }}</td>
                        <td class="text-right">{{ formatted_price($creditNote->amount, $creditNote->currency) }}</td>
                    </tr>
                    <tr>
                        <td class="label">{{ __('store.vat') }}</td>
                        <td class="text-right">{{ formatted_price($creditNote->tax, $creditNote->currency) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td class="label">{{ __('store.total') }}</td>
                        <td class="text-right">{{ formatted_price($creditNote->amount + $creditNote->tax, $creditNote->currency) }}</td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <footer class="invoice-footer">
            <div class="copyright">
                <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </footer>

    </div>

</body>

</html>
