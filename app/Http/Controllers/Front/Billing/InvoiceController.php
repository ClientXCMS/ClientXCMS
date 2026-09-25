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
use App\Http\Requests\Billing\ClientInvoiceExportRequest;
use App\Models\Billing\Invoice;
use App\Services\Billing\InvoiceFilterService;
use App\Services\Billing\InvoiceStatisticsService;
use App\Services\InvoiceExporterService;
use App\Services\Store\GatewayService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request, InvoiceFilterService $filterService, InvoiceStatisticsService $statisticsService)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|array',
            'status.*' => 'string|in:'.implode(',', array_keys(Invoice::FILTERS)),
            'currency' => 'nullable|string|size:3',
            'filter' => 'nullable|string|in:'.implode(',', array_keys(Invoice::FILTERS)),
        ]);
        if (! empty($validated['filter']) && empty($validated['status']) && $validated['filter'] !== 'all') {
            $validated['status'] = [$validated['filter']];
        }
        $invoices = $filterService->apply(Invoice::accessibleBy($request->user()), $validated, false)
            ->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $statistics = $statisticsService->forCustomer($request->user(), $validated['currency'] ?? null);

        return view('front.billing.invoices.index', [
            'invoices' => $invoices,
            'filters' => Invoice::FILTERS,
            'filter' => $validated['filter'] ?? null,
            'invoiceFilters' => $validated,
            'invoiceStatistics' => $statistics,
        ]);
    }

    public function export(ClientInvoiceExportRequest $request, InvoiceFilterService $filterService)
    {
        $invoices = $filterService->apply(
            Invoice::accessibleBy($request->user(), 'invoice.download'),
            $request->validated(),
            false
        )->orderBy('created_at')->get();
        if ($invoices->isEmpty()) {
            return back()->with('error', __('global.no_results'));
        }

        $path = InvoiceExporterService::exportInvoices($invoices, $request->validated('format'), InvoiceExporterService::PROFILE_CLIENT);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function show(Invoice $invoice)
    {
        abort_if(! auth()->user()->hasInvoicePermission($invoice, 'invoice.show'), 404);

        $customer = $invoice->customer;
        $address = $invoice->billing_address;
        $fiscalParties = $invoice->fiscalPartiesForPdf();
        $gateways = GatewayService::getAvailable();
        if ($invoice->isDraft()) {
            return abort(404);
        }

        return view('front.billing.invoices.show', compact('invoice', 'address', 'fiscalParties', 'customer', 'gateways'));
    }

    public function pay(Invoice $invoice, string $gateway)
    {
        abort_if(! auth()->user()->hasInvoicePermission($invoice, 'invoice.pay'), 404);
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
        abort_if(! auth()->user()->hasInvoicePermission($invoice, 'invoice.download'), 404);

        return $invoice->download();
    }

    public function pdf(Invoice $invoice)
    {
        abort_if(! auth()->user()->hasInvoicePermission($invoice, 'invoice.download'), 404);

        return $invoice->pdf();
    }

    public function balance(Request $request, Invoice $invoice)
    {
        abort_if(! auth()->user()->hasInvoicePermission($invoice, 'invoice.balance'), 404);
        abort_if(! setting('allow_add_balance_to_invoices'), 404);
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);
        $amount = (float) $validated['amount'];
        $userBalance = (float) $invoice->customer->balance;
        if ($amount > $userBalance) {
            return redirect()->route('front.invoices.show', $invoice)->with('error', __('client.invoices.balance.balance_not_enough'));
        }
        if ($amount > $invoice->total) {
            $amount = (float) $invoice->total;
        }
        if (! $invoice->addBalance($amount)) {
            return redirect()->route('front.invoices.show', $invoice)->with('error', __('client.invoices.balance.balance_not_enough'));
        }

        return redirect()->route('front.invoices.show', $invoice)->with('success', __('client.invoices.balance.success'));
    }
}
