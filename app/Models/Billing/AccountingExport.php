<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class AccountingExport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_WAITING_PAYMENT = 'waiting_payment';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_MANUAL_REVIEW = 'manual_review';

    protected $fillable = ['exportable_type', 'exportable_id', 'provider', 'event_type', 'status', 'idempotency_key', 'payload_sha256', 'external_id', 'last_error_code', 'last_error_message', 'response', 'submitted_at', 'completed_at'];

    protected $casts = ['response' => 'encrypted:array', 'submitted_at' => 'datetime', 'completed_at' => 'datetime'];

    public function exportable()
    {
        return $this->morphTo();
    }
}
