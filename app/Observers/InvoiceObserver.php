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


namespace App\Observers;

use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceLog;
use App\Models\Provisioning\Service;

class InvoiceObserver
{
    public function updated(Invoice $invoice)
    {
        if ($invoice->isDirty('status')) {
            $status = $invoice->status;
            if ($status == Invoice::STATUS_PAID) {
                InvoiceLog::log($invoice, InvoiceLog::PAID_INVOICE, ['gateway' => $invoice->gateway->uuid, 'external_id' => $invoice->external_id]);
            }
            if ($status == Invoice::STATUS_FAILED) {
                InvoiceLog::log($invoice, InvoiceLog::FAILED_INVOICE, ['gateway' => $invoice->gateway->uuid, 'external_id' => $invoice->external_id]);
            }
            if ($status == Invoice::STATUS_DRAFT) {
                InvoiceLog::log($invoice, InvoiceLog::DRAFT_INVOICE);
            }
            if ($status == Invoice::STATUS_CANCELLED) {
                InvoiceLog::log($invoice, InvoiceLog::CANCEL_INVOICE);
            }
            if ($status == Invoice::STATUS_REFUNDED) {
                InvoiceLog::log($invoice, InvoiceLog::REFUND_INVOICE);
            }
            if ($status == Invoice::STATUS_PENDING) {
                InvoiceLog::log($invoice, InvoiceLog::PENDING_INVOICE);
            }
        }
    }
    public function deleted(Invoice $invoice)
    {
        InvoiceLog::log($invoice, InvoiceLog::DELETE_INVOICE);
        if (Service::where('invoice_id', $invoice->id)->count() > 0) {
            Service::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
        }
    }

    public function creating(Invoice $model)
    {
        $model->uuid = generate_uuid(Invoice::class);
        $model->billing_address = $model->customer->generateBillingAddress();
    }
}
