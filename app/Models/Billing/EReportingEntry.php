<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class EReportingEntry extends Model
{
    protected $fillable = ['period_id', 'source_type', 'source_id', 'type', 'fiscal_date', 'customer_scope', 'country', 'currency', 'vat_rate', 'tax_category', 'operation_category', 'amount_ht', 'amount_tax', 'amount_ttc', 'fingerprint', 'snapshot'];

    protected $casts = ['fiscal_date' => 'date', 'vat_rate' => 'decimal:4', 'amount_ht' => 'decimal:2', 'amount_tax' => 'decimal:2', 'amount_ttc' => 'decimal:2', 'snapshot' => 'array'];

    public function period()
    {
        return $this->belongsTo(EReportingPeriod::class, 'period_id');
    }

    public function source()
    {
        return $this->morphTo();
    }
}
