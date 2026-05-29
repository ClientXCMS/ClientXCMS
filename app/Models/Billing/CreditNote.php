<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Models\Billing;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * v2.16 — Credit note (a.k.a. "avoir") emitted against an existing
 * invoice. Used when a refund cannot legally be expressed as a simple
 * deletion of the original invoice (in France: never).
 *
 * The numbering scheme mirrors {@see InvoiceSequenceService} so the
 * counter is atomic.
 *
 * @property int $id
 * @property string $credit_note_number
 * @property int $invoice_id
 * @property int $customer_id
 * @property string|null $reason
 * @property float $amount
 * @property float $tax
 * @property string $currency
 * @property string|null $pdf_sha256
 * @property int|null $issued_by_admin_id
 */
class CreditNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'credit_note_number',
        'invoice_id',
        'customer_id',
        'reason',
        'amount',
        'tax',
        'currency',
        'pdf_sha256',
        'issued_by_admin_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'tax' => 'float',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'issued_by_admin_id');
    }

    /**
     * Generate the credit note number using the same atomic counter
     * pattern as InvoiceSequenceService. Prefix is configurable so
     * operators can keep their numbering distinct from invoices.
     */
    public static function generateNumber(?string $date = null): string
    {
        $prefix = setting('billing_credit_note_prefix', 'AVOIR');
        $yearMonth = $date ?? now()->format('Y-m');

        return DB::transaction(function () use ($prefix, $yearMonth) {
            $row = DB::table('invoice_sequences')
                ->where('prefix', $prefix)
                ->where('year_month', $yearMonth)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table('invoice_sequences')->insert([
                    'prefix' => $prefix,
                    'year_month' => $yearMonth,
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $next = 1;
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
}
