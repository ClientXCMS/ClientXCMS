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
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    public function notification(Request $request, string $gateway)
    {
        try {
            $gateway = Gateway::where('uuid', $gateway)->first();
            abort_if(! $gateway, 404);

            return $gateway->paymentType()->notification($gateway, $request);
        } catch (WrongPaymentException $e) {
            logger()->error($e->getMessage());

            return abort(404);
        }
    }

    public function cancel(Invoice $invoice)
    {
        $invoice->cancel();

        return redirect()->route('front.invoices.show', $invoice)->with('warning', __('global.invoice_was_cancelled'));
    }

    public function return(Request $request, Invoice $invoice, string $gateway)
    {
        try {
            $gateway = Gateway::where('uuid', $gateway)->first();
            abort_if(! $gateway, 404);

            return $gateway->processPayment($invoice, $request);
        } catch (WrongPaymentException $e) {
            logger()->error($e->getMessage());

            return redirect()->route('front.invoices.show', $invoice)->with('error', __('store.checkout.wrong_payment'));
        }
    }

    public function sourceReturn(Request $request, string $gateway)
    {
        try {
            $gateway = Gateway::where('uuid', $gateway)->first();
            abort_if(! $gateway, 404);
            if (method_exists($gateway->paymentType(), 'sourceReturn')) {
                return $gateway->paymentType()->sourceReturn($request);
            }

            return abort(404);
        } catch (WrongPaymentException $e) {
            logger()->error($e->getMessage());

            return redirect('/');
        }
    }
}
