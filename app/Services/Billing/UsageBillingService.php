<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Services\Billing;

use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceUsageMetric;
use App\Models\Store\ProductMeteredRate;
use App\Services\Store\TaxesService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * v2.16 — End-to-end usage-based ("pay-as-you-go") billing pipeline.
 *
 * Flow:
 *   1. Extensions push samples via {@see recordMetric()}.
 *   2. At the end of each billing period (typically the 1st of the
 *      month via `usage:bill-cycle`) the aggregator computes the PEAK
 *      per metric_key per service — the most expensive moment of the
 *      month — and turns each peak into an InvoiceItem.
 *   3. The resulting Invoice ships through the regular payment
 *      pipeline (Stripe / PayPal / Mollie / …).
 *
 * Why peak and not average? In hosting, what matters is the worst
 * case the customer needed to be provisioned for — that's what we
 * actually consumed in capacity. Average punishes spiky workloads
 * incorrectly.
 */
class UsageBillingService
{
    /**
     * Record a usage sample. Caller-provided `capturedAt` defaults to
     * "now" so legacy modules that just call this without a timestamp
     * still work.
     */
    public function recordMetric(Service $service, string $metricKey, float $value, ?Carbon $capturedAt = null): ServiceUsageMetric
    {
        return ServiceUsageMetric::create([
            'service_id' => $service->id,
            'metric_key' => $metricKey,
            'value' => $value,
            'captured_at' => $capturedAt ?? Carbon::now(),
        ]);
    }

    /**
     * Peak value observed for a given service + metric over the
     * window [periodStart, periodEnd]. Returns 0.0 when no sample
     * matches — the caller's `chargeFor(0)` will then bill nothing
     * (everything is within the included quantity).
     */
    public function peakFor(Service $service, string $metricKey, Carbon $periodStart, Carbon $periodEnd): float
    {
        return (float) ServiceUsageMetric::query()
            ->where('service_id', $service->id)
            ->where('metric_key', $metricKey)
            ->whereBetween('captured_at', [$periodStart, $periodEnd])
            ->max('value');
    }

    /**
     * Build (and persist) a draft invoice that bills the metered
     * resources of one customer for the supplied billing window.
     * Returns null when the customer has no metered service or every
     * peak fits inside the "included_quantity" allowance.
     *
     * The invoice is created with status 'pending' so the existing
     * dunning / payment retry pipeline picks it up.
     */
    public function generateMonthlyInvoice(int $customerId, Carbon $periodStart, Carbon $periodEnd): ?Invoice
    {
        $services = Service::query()
            ->where('customer_id', $customerId)
            ->whereIn('status', [Service::STATUS_ACTIVE, Service::STATUS_SUSPENDED])
            ->whereNotNull('product_id')
            ->with('product')
            ->get();

        if ($services->isEmpty()) {
            return null;
        }

        $items = collect();
        $currency = null;

        foreach ($services as $service) {
            $rates = ProductMeteredRate::where('product_id', $service->product_id)->get();
            if ($rates->isEmpty()) {
                continue;
            }
            $currency ??= $rates->first()->currency;

            foreach ($rates as $rate) {
                $peak = $this->peakFor($service, $rate->metric_key, $periodStart, $periodEnd);
                $charge = $rate->chargeFor($peak);
                if ($charge <= 0) {
                    continue;
                }
                $items->push([
                    'rate' => $rate,
                    'service' => $service,
                    'peak' => $peak,
                    'price_ht' => $charge,
                ]);
            }
        }

        if ($items->isEmpty()) {
            return null;
        }

        $days = (int) (setting('remove_pending_invoice', 0) ?: 7);
        $invoice = Invoice::create([
            'customer_id' => $customerId,
            'currency' => $currency ?: 'EUR',
            'status' => Invoice::STATUS_PENDING,
            'due_date' => now()->addDays($days),
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'notes' => sprintf(
                'Usage billing %s → %s',
                $periodStart->toDateString(),
                $periodEnd->toDateString()
            ),
        ]);

        foreach ($items as $line) {
            /** @var ProductMeteredRate $rate */
            $rate = $line['rate'];
            $service = $line['service'];
            $priceHt = (float) $line['price_ht'];

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'name' => sprintf('%s — %s', $service->name, $rate->label),
                'description' => sprintf(
                    'Peak %s %s (included %s, billed %s @ %s)',
                    rtrim(rtrim(number_format($line['peak'], 4, '.', ''), '0'), '.'),
                    $rate->unit ?: '',
                    rtrim(rtrim(number_format($rate->included_quantity, 4, '.', ''), '0'), '.'),
                    rtrim(rtrim(number_format(max(0.0, $line['peak'] - $rate->included_quantity), 4, '.', ''), '0'), '.'),
                    number_format($rate->unit_price, 4, '.', '')
                ),
                'quantity' => 1,
                'unit_price_ht' => $priceHt,
                'unit_price_ttc' => TaxesService::getPriceWithVat($priceHt),
                'unit_setup_ht' => 0,
                'unit_setup_ttc' => 0,
                'type' => 'usage',
                'related_id' => $service->id,
                'data' => [
                    'metric_key' => $rate->metric_key,
                    'peak' => $line['peak'],
                    'included' => $rate->included_quantity,
                    'unit_price' => $rate->unit_price,
                    'period_start' => $periodStart->toDateTimeString(),
                    'period_end' => $periodEnd->toDateTimeString(),
                ],
            ]);
        }

        $invoice->recalculate();

        return $invoice;
    }

    /**
     * Convenience helper for the artisan command — bills every
     * customer that has at least one metric in the window.
     */
    public function generateInvoicesForPeriod(Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $customerIds = ServiceUsageMetric::query()
            ->whereBetween('captured_at', [$periodStart, $periodEnd])
            ->join('services', 'services.id', '=', 'service_usage_metrics.service_id')
            ->distinct()
            ->pluck('services.customer_id');

        return $customerIds->map(fn (int $id) => $this->generateMonthlyInvoice($id, $periodStart, $periodEnd))
            ->filter()
            ->values();
    }
}
