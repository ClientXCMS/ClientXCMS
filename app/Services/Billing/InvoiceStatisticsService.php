<?php

namespace App\Services\Billing;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;

class InvoiceStatisticsService
{
    public function forCustomer(Customer $customer, ?string $requestedCurrency = null): array
    {
        $base = Invoice::accessibleBy($customer, 'invoice.download')
            ->where('status', '!=', Invoice::STATUS_DRAFT);
        $currencies = (clone $base)->whereNotNull('currency')->distinct()->orderBy('currency')->pluck('currency')->values();
        $currency = strtoupper((string) $requestedCurrency);
        if (! $currencies->contains($currency)) {
            $currency = (string) ($currencies->first() ?: setting('store_currency', 'EUR'));
        }

        $currencyQuery = (clone $base)->where('currency', $currency);
        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();
        $paidByMonth = array_fill(0, 12, 0.0);

        (clone $currencyQuery)->where('status', Invoice::STATUS_PAID)
            ->whereBetween('paid_at', [$yearStart, $yearEnd])
            ->get(['paid_at', 'total'])
            ->each(function (Invoice $invoice) use (&$paidByMonth) {
                if ($invoice->paid_at) {
                    $paidByMonth[$invoice->paid_at->month - 1] += (float) $invoice->total;
                }
            });

        return [
            'currency' => $currency,
            'available_currencies' => $currencies->all(),
            'outstanding' => (float) (clone $currencyQuery)->where('status', Invoice::STATUS_PENDING)->sum('total'),
            'paid_current_month' => (float) (clone $currencyQuery)->where('status', Invoice::STATUS_PAID)
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total'),
            'paid_by_month' => $paidByMonth,
        ];
    }
}
