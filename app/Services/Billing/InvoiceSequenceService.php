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

namespace App\Services\Billing;

use App\Models\Billing\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceSequenceService
{
    public static function nextNumber(?string $date = null, bool $creation = true): string
    {
        $prefix = setting('billing_invoice_prefix', 'CTX');
        if ($creation && InvoiceService::getBillingType() === InvoiceService::PRO_FORMA) {
            $prefix = $prefix.'-PROFORMA';
        }

        $yearMonth = $date ?? now()->format('Y-m');

        DB::table('invoice_sequences')->insertOrIgnore([
            'prefix' => $prefix,
            'year_month' => $yearMonth,
            'last_number' => self::bootstrapCounter($prefix, $yearMonth),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::transaction(function () use ($prefix, $yearMonth) {
            $row = DB::table('invoice_sequences')
                ->where('prefix', $prefix)
                ->where('year_month', $yearMonth)
                ->lockForUpdate()
                ->first();

            $next = (int) $row->last_number + 1;

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
        // Escape LIKE wildcards (prefix is admin-controlled setting).
        $likePrefix = addcslashes($prefix, '\\%_');
        $like = $likePrefix.'-'.$yearMonth.'-%';
        $max = Invoice::withTrashed()
            ->where('invoice_number', 'like', $like)
            ->pluck('invoice_number')
            ->map(function ($num) use ($prefix, $yearMonth) {
                $needle = $prefix.'-'.$yearMonth.'-';
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
