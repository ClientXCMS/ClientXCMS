<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class EReportingPeriod extends Model
{
    public const TYPE_TRANSACTION = 'transaction';

    public const TYPE_PAYMENT = 'payment';

    protected $fillable = ['type', 'regime', 'provider', 'period_start', 'period_end', 'due_at', 'status', 'idempotency_key', 'payload_sha256', 'external_id', 'artifact_path', 'totals', 'last_error', 'submitted_at'];

    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'due_at' => 'datetime', 'submitted_at' => 'datetime', 'totals' => 'array'];

    public function entries()
    {
        return $this->hasMany(EReportingEntry::class, 'period_id');
    }

    public function electronicDocuments()
    {
        return $this->morphMany(ElectronicDocument::class, 'documentable');
    }
}
