<?php

namespace App\Events\Core\Invoice;

use App\Models\Billing\PaymentTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentTransactionRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public PaymentTransaction $transaction) {}
}
