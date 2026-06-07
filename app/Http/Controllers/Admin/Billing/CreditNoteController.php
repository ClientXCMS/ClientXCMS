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

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Billing\CreditNote;
use App\Models\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditNoteController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        staff_aborts_permission('admin.manage_invoices');

        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);

        if ($invoice->customer_id !== $customer->id) {
            abort(403);
        }

        $totalTtc = (float) $request->amount;

        // Ensure the credit note does not exceed the invoice total
        // We can check the sum of existing credit notes on this invoice
        $existingCredits = $invoice->creditNotes()->sum(DB::raw('amount + tax'));
        $remainingAmount = $invoice->total - $existingCredits;

        if (round($totalTtc, 2) > round($remainingAmount, 2)) {
            return back()->with('error', __('admin.credit_notes.amount_exceeds_remaining', [
                'remaining' => formatted_price($remainingAmount, $invoice->currency)
            ]));
        }

        // Calculate tax components
        $taxRate = $invoice->subtotal > 0 ? ($invoice->tax / $invoice->subtotal) : 0;
        $taxAmount = $totalTtc * ($taxRate / (1 + $taxRate));
        $amountHt = $totalTtc - $taxAmount;

        $creditNote = DB::transaction(function () use ($customer, $invoice, $amountHt, $taxAmount, $totalTtc, $request) {
            $creditNote = CreditNote::create([
                'credit_note_number' => CreditNote::generateNumber(),
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'amount' => $amountHt,
                'tax' => $taxAmount,
                'currency' => $invoice->currency,
                'reason' => $request->reason,
                'issued_by_admin_id' => auth('admin')->id(),
            ]);

            // Add funds to customer balance
            $customer->addFund($totalTtc, __('admin.credit_notes.fund_reason', ['number' => $creditNote->credit_note_number]));

            // Log the action
            ActionLog::log(
                ActionLog::RESOURCE_CREATED,
                CreditNote::class,
                $creditNote->id,
                auth('admin')->id(),
                $customer->id,
                ['model' => 'Credit Note #' . $creditNote->credit_note_number]
            );

            return $creditNote;
        });

        // Generate PDF
        $creditNote->generatePdf(true);

        return back()->with('success', __('admin.credit_notes.created_success', ['number' => $creditNote->credit_note_number]));
    }

    public function pdf(CreditNote $creditNote)
    {
        staff_aborts_permission('admin.show_invoices');
        return $creditNote->pdf();
    }

    public function download(CreditNote $creditNote)
    {
        staff_aborts_permission('admin.show_invoices');
        return $creditNote->download();
    }

    public function destroy(CreditNote $creditNote)
    {
        staff_aborts_permission('admin.manage_invoices');

        $number = $creditNote->credit_note_number;
        $customerId = $creditNote->customer_id;

        DB::transaction(function () use ($creditNote, $customerId, $number) {
            // Log resource deletion
            ActionLog::log(
                ActionLog::RESOURCE_DELETED,
                CreditNote::class,
                $creditNote->id,
                auth('admin')->id(),
                $customerId,
                ['model' => 'Credit Note #' . $number]
            );

            $creditNote->delete();
        });

        return back()->with('success', __('admin.credit_notes.deleted_success', ['number' => $number]));
    }
}
