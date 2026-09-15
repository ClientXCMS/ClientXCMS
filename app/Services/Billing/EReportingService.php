<?php

namespace App\Services\Billing;

use App\Contracts\Billing\EReportingScheduleInterface;
use App\Models\Billing\EReportingEntry;
use App\Models\Billing\EReportingPeriod;
use App\Models\Billing\Invoice;
use App\Models\Billing\PaymentTransaction;
use Illuminate\Database\Eloquent\Model;

class EReportingService
{
    public function __construct(private EReportingScheduleInterface $schedule) {}

    public function recordInvoice(Invoice $invoice): void
    {
        $invoice->loadMissing('items');
        foreach ($invoice->items as $item) {
            $ht = round(((float) $item->unit_price_ht + (float) $item->unit_setup_ht) * $item->quantity - (float) $item->discountTotal(), 2);
            $tax = round($ht * (float) $item->vat_rate / 100, 2);
            $this->record($invoice, EReportingPeriod::TYPE_TRANSACTION, $invoice->issued_at ?? $invoice->created_at, $ht, $tax, [
                'vat_rate' => $item->vat_rate ?? 0, 'tax_category' => $item->tax_category ?? 'standard',
                'operation_category' => $item->operation_category ?? data_get($invoice->billing_snapshot, 'tax.operation_category', 'services'),
            ], 'invoice:'.$invoice->uuid.':'.$item->id);
        }
        if ((float) $invoice->balance > 0 && $invoice->items->isNotEmpty()) {
            $item = $invoice->items->first();
            $rate = (float) ($item->vat_rate ?? 0);
            $ht = -(float) $invoice->balance;
            $this->record($invoice, EReportingPeriod::TYPE_TRANSACTION, $invoice->issued_at ?? $invoice->created_at, $ht, round($ht * $rate / 100, 2), [
                'vat_rate' => $rate, 'tax_category' => $item->tax_category ?? 'standard',
                'operation_category' => $item->operation_category ?? data_get($invoice->billing_snapshot, 'tax.operation_category', 'services'),
            ], 'invoice:'.$invoice->uuid.':balance');
        }
    }

    public function recordPayment(PaymentTransaction $transaction): void
    {
        $invoice = $transaction->invoice;
        if (filter_var(data_get($invoice->billing_snapshot, 'tax.vat_on_debits', false), FILTER_VALIDATE_BOOL)) {
            return;
        }
        $categories = $invoice->items()->pluck('operation_category')->filter();
        if ($categories->isNotEmpty() && ! $categories->contains(fn ($value) => in_array($value, ['service', 'services', 'mixed'], true))) {
            return;
        }
        $ratio = (float) $invoice->total !== 0.0 ? (float) $transaction->amount / (float) $invoice->total : 0;
        $this->record($transaction, EReportingPeriod::TYPE_PAYMENT, $transaction->occurred_at, (float) $invoice->subtotal * $ratio, (float) $invoice->tax * $ratio, [
            'vat_rate' => 0, 'tax_category' => 'mixed', 'operation_category' => 'services',
        ], 'payment:'.$transaction->uuid);
    }

    private function record(Model $source, string $type, $date, float $ht, float $tax, array $taxData, string $fingerprintSeed): void
    {
        $regime = (string) setting('einvoicing_vat_regime', 'real_normal_monthly');
        $timezone = (string) setting('einvoicing_timezone', 'Europe/Paris');
        $provider = (string) setting('einvoicing_provider', 'local');
        $window = $this->schedule->periodFor($type, $date, $regime, $timezone);
        $period = EReportingPeriod::firstOrCreate([
            'type' => $type, 'provider' => $provider, 'period_start' => $window['start'], 'period_end' => $window['end'],
        ], ['regime' => $regime, 'due_at' => $window['due_at'], 'status' => 'open', 'idempotency_key' => hash('sha256', implode('|', [$provider, $type, $window['start'], $window['end']]))]);
        $invoice = $source instanceof Invoice ? $source : $source->invoice;
        $sign = $source instanceof PaymentTransaction && $source->type === 'refund' ? -1 : 1;
        EReportingEntry::firstOrCreate(['fingerprint' => hash('sha256', $fingerprintSeed)], [
            'period_id' => $period->id, 'source_type' => $source::class, 'source_id' => $source->getKey(), 'type' => $type,
            'fiscal_date' => $date->toDateString(), 'customer_scope' => strtoupper((string) $invoice->customer->country) === 'FR' ? 'domestic_b2c' : 'international',
            'country' => strtoupper((string) $invoice->customer->country), 'currency' => strtoupper($invoice->currency),
            'vat_rate' => $taxData['vat_rate'], 'tax_category' => $taxData['tax_category'], 'operation_category' => $taxData['operation_category'],
            'amount_ht' => number_format($ht * $sign, 2, '.', ''), 'amount_tax' => number_format($tax * $sign, 2, '.', ''), 'amount_ttc' => number_format(($ht + $tax) * $sign, 2, '.', ''),
            'snapshot' => ['invoice_number' => $invoice->identifier(), 'routing' => data_get($invoice->billing_snapshot, 'tax.electronic_routing')],
        ]);
    }
}
