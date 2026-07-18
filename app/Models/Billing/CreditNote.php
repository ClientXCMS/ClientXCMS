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

namespace App\Models\Billing;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use App\Theme\ThemeManager;
use Barryvdh\DomPDF\PDF;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Credit note (a.k.a. "avoir") emitted against an existing
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
        $prefix = setting('billing_invoice_prefix', 'CTX') . '-AVOIR';
        $yearMonth = $date ?? now()->format('Y-m');

        // Atomic pre-create, cf. InvoiceSequenceService::nextNumber.
        DB::table('invoice_sequences')->insertOrIgnore([
            'prefix' => $prefix,
            'year_month' => $yearMonth,
            'last_number' => 0,
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

    public function download(): Response
    {
        if (Storage::disk('local')->exists($this->getPdfPath())) {
            return Storage::disk('local')->download($this->getPdfPath(), $this->credit_note_number.'.pdf');
        }

        $pdf = $this->generatePdf();

        return $pdf->download($this->credit_note_number.'.pdf');
    }

    public function pdf(): Response
    {
        if (Storage::disk('local')->exists($this->getPdfPath())) {
            $fullPath = Storage::disk('local')->path($this->getPdfPath());

            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$this->credit_note_number.'.pdf"',
            ]);
        }
        $pdf = $this->generatePdf(false);

        return $pdf->stream($this->credit_note_number.'.pdf');
    }

    public function getPdfPath(): string
    {
        return 'credit_notes/'.$this->getPdfName();
    }

    public function getPdfName(): string
    {
        $date = $this->created_at ? $this->created_at : now();

        return $date->format('Y').'/'.$date->format('m').'/'.$this->credit_note_number.'.pdf';
    }

    public function generatePdf(bool $save = true): PDF
    {
        $filename = 'credit_notes/'.$this->getPdfName();
        $domain = rtrim(config('app.url'), '/');
        if (str_contains($domain, 'localhost')) {
            $logoSrc = '/'.setting('app_logo_text');
        } else {
            $logoSrc = $domain.setting('app_logo_text');
        }

        $primaryColor = ThemeManager::getColorsArray()['600'];
        $color = ThemeManager::getContrastColor($primaryColor);
        $pdf = \PDF::loadView('front.billing.credit_notes.pdf', [
            'creditNote' => $this,
            'customer' => $this->customer,
            'color' => $color,
            'address' => $this->invoice->billing_address,
            'logoSrc' => $logoSrc,
            'primaryColor' => $primaryColor,
        ]);
        if ($save) {
            $bytes = $pdf->output();
            Storage::put($filename, $bytes);
            try {
                $this->forceFill(['pdf_sha256' => hash('sha256', $bytes)])->saveQuietly();
            } catch (\Throwable $e) {
                logger()->warning('billing.credit_note.pdf_hash_failed', [
                    'credit_note_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $pdf;
    }
}
