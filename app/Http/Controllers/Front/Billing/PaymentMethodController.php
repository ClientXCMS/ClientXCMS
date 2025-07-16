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
 * Year: 2025
 */
namespace App\Http\Controllers\Front\Billing;

use App\Models\Account\Customer;
use App\Models\Billing\Gateway;
use App\Services\Store\GatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class PaymentMethodController extends \App\Http\Controllers\Controller
{
    public function index()
    {
        $gateways = GatewayService::getAvailable(-1);
        $customer = auth()->user();
        $gatewaysOptions = collect($gateways)->filter(function ($gateway) {
            return $gateway->uuid != 'balance';
        })->mapWithKeys(function ($gateway) {
            return [$gateway->uuid => $gateway->name];
        });
        $sources = collect($gateways)->map(function ($gateway) use ($customer) {
            return $gateway->paymentType()->getSources($customer);
        })->flatten();
        $gateways = collect($gateways)->filter(function ($gateway) {
            return ! empty($gateway->paymentType()->sourceForm());
        });

        return view('front.billing.payment-methods.index', compact('gateways', 'sources', 'gatewaysOptions'));
    }

    public function add(Gateway $gateway, Request $request)
    {
        if (empty($gateway->paymentType()->sourceForm())) {
            return back()->with('error', __('client.payment-methods.errors.not_supported'));
        }
        try {
            $gateway->paymentType()->addSource($request);
            Cache::delete('payment_methods_'.auth()->user()->id);

            return back()->with('success', __('client.payment-methods.success'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function default(Request $request, string $source)
    {
        if ($request->has('customer_id')) {
            if (staff_has_permission('admin.show_payment_methods')) {
                $customer = Customer::find($request->get('customer_id'));
            } else {
                abort(404);
            }
        } else {
            $customer = auth()->user();
            if ($customer == null) {
                abort(404);
            }
        }
        $gateways = GatewayService::getAvailable(-1);
        $gateway = collect($gateways)->first(function ($gateway) use ($source, $customer) {
            return $gateway->paymentType()->getSource($customer, $source);
        });
        if (! $gateway) {
            return back()->with('error', __('client.payment-methods.errors.not_found'));
        }
        $customer->setDefaultPaymentMethod($source);

        return back()->with('success', __('client.payment-methods.defaultsucces'));
    }

    public function delete(Request $request, string $source)
    {
        if ($request->has('customer_id')) {
            if (staff_has_permission('admin.show_payment_methods')) {
                $customer = Customer::find($request->get('customer_id'));
            } else {
                abort(404);
            }
        } else {
            $customer = auth()->user();
        }
        $source = $customer->paymentMethods()->where('id', $source)->first();
        if (! $source) {
            return back()->with('error', __('client.payment-methods.errors.not_found'));
        }
        $gateway = Gateway::where('uuid', $source->gateway_uuid)->first();
        if (! $gateway) {
            return back()->with('error', __('client.payment-methods.errors.not_found'));
        }
        $gateway->paymentType()->removeSource($source);
        try {
            Cache::delete('payment_methods_'.$customer->id);
        } catch (InvalidArgumentException $e) {
        }

        return back()->with('success', __('client.payment-methods.deleted'));
    }
}
