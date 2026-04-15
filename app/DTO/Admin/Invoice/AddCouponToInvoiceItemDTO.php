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

namespace App\DTO\Admin\Invoice;

use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Store\Product;

class AddCouponToInvoiceItemDTO
{
    public int $quantity;

    public string $billing;

    public ?Product $product = null;

    public Invoice $invoice;

    public InvoiceItem $invoiceItem;

    public float $unit_price_ht;

    public float $unit_price_ttc;

    public float $unit_setup_ht;

    public float $unit_setup_ttc;

    public function __construct(array $params, InvoiceItem $invoiceItem)
    {
        $this->unit_price_ht = $params['unit_price_ht'];
        $this->unit_price_ttc = $params['unit_price_ttc'];
        $this->unit_setup_ht = $params['unit_setup_ht'];
        $this->unit_setup_ttc = $params['unit_setup_ttc'];
        $this->quantity = $params['quantity'];
        $this->billing = $params['billing'] ?? $invoiceItem->data['billing'] ?? 'monthly';
        $this->invoiceItem = $invoiceItem;
        $this->invoice = $invoiceItem->invoice;
    }
}
