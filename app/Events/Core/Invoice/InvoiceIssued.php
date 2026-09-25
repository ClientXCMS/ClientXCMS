<?php

namespace App\Events\Core\Invoice;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceIssued extends InvoiceEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
}
