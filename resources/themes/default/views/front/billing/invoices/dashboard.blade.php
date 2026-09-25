@php
    $hasPaidChartData = collect($invoiceStatistics['paid_by_month'])->contains(fn ($amount) => (float) $amount !== 0.0);
@endphp

@if(count($invoiceStatistics['available_currencies']) > 1)
    <div class="flex justify-end mb-4">
        <form method="GET" action="{{ route('front.invoices.index') }}">
            <select name="currency" onchange="this.form.submit()" class="input-text py-2 px-3">
                @foreach($invoiceStatistics['available_currencies'] as $currency)
                    <option value="{{ $currency }}" @selected($invoiceStatistics['currency'] === $currency)>{{ $currency }}</option>
                @endforeach
            </select>
        </form>
    </div>
@endif

<div class="grid sm:grid-cols-2 gap-4 mb-6">
    <div class="card">
        <p class="text-sm text-gray-500">{{ __('client.invoices.stats.outstanding') }}</p>
        <p class="text-2xl font-semibold">{{ formatted_price($invoiceStatistics['outstanding'], $invoiceStatistics['currency']) }}</p>
    </div>
    <div class="card">
        <p class="text-sm text-gray-500">{{ __('client.invoices.stats.paid_this_month') }}</p>
        <p class="text-2xl font-semibold">{{ formatted_price($invoiceStatistics['paid_current_month'], $invoiceStatistics['currency']) }}</p>
    </div>
</div>
@if($hasPaidChartData)
    <div class="card mb-6">
        <div class="flex flex-wrap justify-between gap-3 mb-4">
            <h2 class="text-lg font-semibold">{{ __('client.invoices.stats.yearly_paid') }} ({{ $invoiceStatistics['currency'] }})</h2>
        </div>
        <div class="h-64">
            <canvas id="invoice-paid-chart"
                    data-values='@json($invoiceStatistics['paid_by_month'])'
                    data-currency="{{ $invoiceStatistics['currency'] }}"
                    data-label="{{ __('client.invoices.stats.yearly_paid') }}"></canvas>
        </div>
    </div>
@endif
