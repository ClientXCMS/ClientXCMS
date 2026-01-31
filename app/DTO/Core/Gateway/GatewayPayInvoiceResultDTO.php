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

namespace App\DTO\Core\Gateway;

use App\Abstracts\PaymentMethodSourceDTO;
use App\Models\Billing\Invoice;

class GatewayPayInvoiceResultDTO
{
    public bool $success;

    public string $message;

    public Invoice $invoice;

    public PaymentMethodSourceDTO $sourceDTO;

    public function __construct(bool $success, string $message, Invoice $invoice, PaymentMethodSourceDTO $sourceDTO)
    {
        $this->success = $success;
        $this->message = $message;
        $this->invoice = $invoice;
        $this->sourceDTO = $sourceDTO;
    }
}
