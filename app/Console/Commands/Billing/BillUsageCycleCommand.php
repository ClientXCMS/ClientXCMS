<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Console\Commands\Billing;

use App\Services\Billing\UsageBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * v2.16 — Closes the previous billing window (default: the month
 * that just ended) and issues a pending Invoice per customer that
 * holds at least one metered service with peaks above the included
 * quantity.
 *
 * Schedule it on the 1st of each month at 03:00 in
 * app/Console/Kernel:
 *   $schedule->command('usage:bill-cycle')->monthlyOn(1, '03:00');
 */
class BillUsageCycleCommand extends Command
{
    protected $signature = 'usage:bill-cycle
                            {--period=last-month : "last-month" or "current-month" or YYYY-MM}';

    protected $description = 'Issue pay-as-you-go invoices for the closed billing window';

    public function handle(UsageBillingService $billing): int
    {
        [$start, $end] = $this->resolvePeriod((string) $this->option('period'));

        $this->line(sprintf('Billing usage between %s and %s', $start, $end));

        $invoices = $billing->generateInvoicesForPeriod($start, $end);

        $this->info(sprintf('Issued %d invoice(s).', $invoices->count()));
        foreach ($invoices as $invoice) {
            $this->line(sprintf('  #%d %s (customer %d) — %s %s',
                $invoice->id,
                $invoice->invoice_number,
                $invoice->customer_id,
                number_format($invoice->total, 2),
                $invoice->currency
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(string $option): array
    {
        if ($option === 'current-month') {
            return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
        }
        if ($option === 'last-month') {
            return [Carbon::now()->subMonthNoOverflow()->startOfMonth(), Carbon::now()->subMonthNoOverflow()->endOfMonth()];
        }
        if (preg_match('/^\d{4}-\d{2}$/', $option)) {
            $start = Carbon::createFromFormat('Y-m-d H:i:s', $option . '-01 00:00:00')->startOfMonth();
            return [$start, $start->copy()->endOfMonth()];
        }
        throw new \InvalidArgumentException('Unknown --period value: ' . $option);
    }
}
