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

namespace App\Http\Controllers\Front\Billing;

use App\Exceptions\WrongPaymentException;
use App\Http\Controllers\Controller;
use App\Models\Billing\Invoice;
use App\Services\Store\GatewayService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('filter')) {
            $filter = $request->get('filter');
            if (! in_array($filter, array_keys(Invoice::FILTERS))) {
                return redirect()->route('front.invoices.index');
            }
            $invoices = Invoice::where('customer_id', auth()->id())->where('status', '!=', Invoice::STATUS_DRAFT)->where('status', $request->get('filter'))->orderBy('created_at', 'desc')->paginate(10);
        } else {
            $filter = null;
            $invoices = Invoice::where('customer_id', auth()->id())->where('status', '!=', Invoice::STATUS_DRAFT)->orderBy('created_at', 'desc')->paginate(10);
        }

        return view('front.billing.invoices.index', [
            'invoices' => $invoices,
            'filter' => $filter,
            'filters' => Invoice::FILTERS,
        ]);
    }

    public function show(Invoice $invoice)
    {
        abort_if($invoice->customer_id != auth()->id(), 404);

        $customer = $invoice->customer;
        $address = $invoice->billing_address;
        $gateways = GatewayService::getAvailable();
        if ($invoice->isDraft()) {
            return abort(404);
        }

        return view('front.billing.invoices.show', compact('invoice', 'address', 'customer', 'gateways'));
    }

    public function pay(Invoice $invoice, string $gateway)
    {
        abort_if($invoice->customer_id != auth()->id(), 404);
        if ($invoice->total == 0) {
            $gateway = \App\Models\Billing\Gateway::where('uuid', 'none')->first();
        } else {
            $gateway = \App\Models\Billing\Gateway::getAvailable()->where('uuid', $gateway)->first();
            if ($gateway === null) {
                return redirect()->route('front.invoices.show', $invoice)->with('error', __('store.checkout.gateway_not_found'));
            }
        }
        try {
            if ($gateway->minimal_amount > $invoice->total) {
                return redirect()->route('front.invoices.show', $invoice)->with('error', __('store.checkout.minimal_amount', ['amount' => formatted_price($gateway->minimal_amount)]));
            }
            if ($invoice->canPay()) {
                return $invoice->pay($gateway, request());
            }

            return redirect()->route('front.invoices.show', $invoice)->with('error', __('client.invoices.invoice_not_payable'));
        } catch (WrongPaymentException $e) {
            logger()->error($e->getMessage());

            return redirect()->route('front.invoices.show', $invoice)->with('error', __('store.checkout.wrong_payment'));
        }
    }

    public function download(Invoice $invoice)
    {
        abort_if($invoice->customer_id != auth()->id(), 404);

        return $invoice->download();
    }

    public function pdf(Invoice $invoice)
    {
        abort_if($invoice->customer_id != auth()->id(), 404);

        return $invoice->pdf();
    }

    public function balance(Request $request, Invoice $invoice)
    {
        abort_if($invoice->customer_id != auth()->id(), 404);
        abort_if(! setting('allow_add_balance_to_invoices'), 404);
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);
        if ($validated['amount'] >= auth('web')->user()->balance && $invoice->balance + $validated['amount'] > $invoice->total) {
            return redirect()->route('front.invoices.show', $request->invoice)->with('error', __('client.invoices.balance.balance_not_enough'));
        }
        $invoice->addBalance($validated['amount']);

        return redirect()->route('front.invoices.show', $invoice)->with('success', __('client.invoices.balance.success'));
    }
}
