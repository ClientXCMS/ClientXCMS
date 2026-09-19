<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = ['uuid', 'invoice_id', 'reverses_id', 'type', 'amount', 'currency', 'payment_method', 'external_id', 'idempotency_key', 'occurred_at', 'metadata'];

    protected $casts = ['amount' => 'decimal:2', 'occurred_at' => 'datetime', 'metadata' => 'array'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function reversedTransaction()
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }
}
