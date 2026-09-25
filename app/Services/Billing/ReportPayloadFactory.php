<?php

namespace App\Services\Billing;

use App\DTO\Billing\Electronic\PaymentReportPayload;
use App\DTO\Billing\Electronic\ReportPayload;
use App\DTO\Billing\Electronic\TransactionReportPayload;
use App\Models\Billing\EReportingPeriod;

class ReportPayloadFactory
{
    public function make(EReportingPeriod $period): ReportPayload
    {
        $rows = $period->entries()->orderBy('fiscal_date')->get()->groupBy(fn($entry) => implode('|', [$entry->fiscal_date->toDateString(), $entry->country, $entry->customer_scope, $entry->vat_rate, $entry->tax_category, $entry->operation_category]))->map(function ($entries) {
            $first = $entries->first();

            return ['date' => $first->fiscal_date->toDateString(), 'country' => $first->country, 'customer_scope' => $first->customer_scope, 'vat_rate' => $first->vat_rate, 'tax_category' => $first->tax_category, 'operation_category' => $first->operation_category, 'amount_ht' => number_format($entries->sum('amount_ht'), 2, '.', ''), 'amount_tax' => number_format($entries->sum('amount_tax'), 2, '.', ''), 'amount_ttc' => number_format($entries->sum('amount_ttc'), 2, '.', '')];
        })->values()->all();
        $totals = ['ht' => number_format(array_sum(array_column($rows, 'amount_ht')), 2, '.', ''), 'tax' => number_format(array_sum(array_column($rows, 'amount_tax')), 2, '.', ''), 'ttc' => number_format(array_sum(array_column($rows, 'amount_ttc')), 2, '.', '')];
        $data = ['type' => $period->type, 'start' => $period->period_start->toDateString(), 'end' => $period->period_end->toDateString(), 'currency' => setting('currency', 'EUR'), 'entries' => $rows, 'totals' => $totals];

        $class = $period->type === EReportingPeriod::TYPE_PAYMENT ? PaymentReportPayload::class : TransactionReportPayload::class;

        return new $class($data['type'], $data['start'], $data['end'], $data['currency'], $rows, $totals, hash('sha256', json_encode($data, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR)));
    }
}
