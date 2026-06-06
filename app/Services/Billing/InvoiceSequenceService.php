<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Services\Billing;

use App\Models\Billing\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * v2.16 — Atomic invoice number generator.
 *
 * Replaces the race-prone `Invoice::where(...)->count() + 1` pattern
 * with a row-level locked counter, one per (prefix, year_month).
 * Backfills the counter from the existing invoice rows on first call
 * so installs upgrading from v2.15 don't restart at 1 mid-month.
 *
 * Returns the FULL invoice number ready to assign to the row, e.g.
 *   "CTX-2026-05-0042"
 *   "CTX-PROFORMA-2026-05-0042"
 *
 * Legal context (FR): CGI art. 289 requires invoice numbers to be
 * issued in a continuous, sequential, chronological order. A race
 * resulting in two invoices sharing a number is a compliance
 * violation. The DB transaction + lockForUpdate make the increment
 * single-writer.
 */
class InvoiceSequenceService
{
    public static function nextNumber(?string $date = null, bool $creation = true): string
    {
        $prefix = setting('billing_invoice_prefix', 'CTX');
        if ($creation && InvoiceService::getBillingType() === InvoiceService::PRO_FORMA) {
            $prefix = $prefix . '-PROFORMA';
        }

        $yearMonth = $date ?? now()->format('Y-m');

        return DB::transaction(function () use ($prefix, $yearMonth) {
            // Lock the row for this prefix + month. If it doesn't
            // exist yet, bootstrap it from the highest existing
            // invoice_number that matches the legacy pattern so we
            // don't restart at 1 on a v2.15 → v2.16 upgrade.
            $row = DB::table('invoice_sequences')
                ->where('prefix', $prefix)
                ->where('year_month', $yearMonth)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                $boot = self::bootstrapCounter($prefix, $yearMonth);
                DB::table('invoice_sequences')->insert([
                    'prefix' => $prefix,
                    'year_month' => $yearMonth,
                    'last_number' => $boot,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $next = $boot + 1;
            } else {
                $next = (int) $row->last_number + 1;
            }

            DB::table('invoice_sequences')
                ->where('prefix', $prefix)
                ->where('year_month', $yearMonth)
                ->update([
                    'last_number' => $next,
                    'updated_at' => now(),
                ]);

            return sprintf('%s-%s-%04d', $prefix, $yearMonth, $next);
        });
    }

    private static function bootstrapCounter(string $prefix, string $yearMonth): int
    {
        // Find the highest 4-digit tail among existing invoices whose
        // number matches "<prefix>-<yyyy-mm>-NNNN".
        $like = $prefix . '-' . $yearMonth . '-%';
        $max = Invoice::withTrashed()
            ->where('invoice_number', 'like', $like)
            ->pluck('invoice_number')
            ->map(function ($num) use ($prefix, $yearMonth) {
                $needle = $prefix . '-' . $yearMonth . '-';
                if (! str_starts_with((string) $num, $needle)) {
                    return 0;
                }
                $tail = substr((string) $num, strlen($needle));
                return is_numeric($tail) ? (int) $tail : 0;
            })
            ->max();

        return (int) ($max ?: 0);
    }
}
