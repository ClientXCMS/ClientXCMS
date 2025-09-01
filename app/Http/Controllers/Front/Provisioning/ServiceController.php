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


namespace App\Http\Controllers\Front\Provisioning;

use App\DTO\Provisioning\ProvisioningTabDTO;
use App\Exceptions\WrongPaymentException;
use App\Http\Controllers\Controller;
use App\Models\Billing\Gateway;
use App\Models\Billing\Subscription;
use App\Models\Provisioning\Service;
use App\Models\Store\Group;
use App\Models\Store\Product;
use App\Rules\isValidBillingDayRule;
use App\Services\Billing\InvoiceService;
use App\Services\Provisioning\ServiceService;
use App\Services\Store\GatewayService;
use App\Traits\Controllers\ServiceControllerTrait;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use ServiceControllerTrait;

    public function index(Request $request)
    {
        $userId = auth('web')->id();
        $filter = $request->get('filter');

        if ($filter) {
            if (! in_array($filter, array_keys($this->getFilters()))) {
                return redirect()->route('front.services.index');
            }
            if (in_array($filter, Service::FILTERS)) {
                $services = Service::where('customer_id', $userId)
                    ->where('status', $filter)
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
            } else {
                $group = Group::where('slug', $filter)->first();
                if (!$group) {
                    return redirect()->route('front.services.index');
                }
                $products = $group->products->pluck('id');
                if ($group->isGroup()) {
                    foreach ($group->groups as $subgroup) {
                        $products = $products->merge($subgroup->products->pluck('id'));
                    }
                }
                $services = Service::where('customer_id', $userId)
                    ->whereIn('product_id', $products)
                    ->where('status', '!=', 'hidden')
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
            }
        } else {
            $filter = null;
            $services = Service::where('customer_id', $userId)
                ->where('status', '!=', 'hidden')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }
        return view('front.provisioning.services.index', [
            'services' => $services,
            'filter' => $filter,
            'filters' => $this->getFilters(),
            'gateways' => GatewayService::getAvailable(),
        ]);
    }


    public function upgrade(Service $service)
    {
        abort_if($service->customer_id != auth()->id(), 404);
        abort_if($service->state == 'pending', 404);
        abort_if(! $service->canUpgrade(), 404);
        $customer = $service->customer;
        $gateways = GatewayService::getAvailable();
        $products = $service->product->getUpgradeProducts();

        return view('front.provisioning.services.upgrade', compact('service', 'customer', 'gateways', 'products'));
    }

    public function options(Service $service)
    {
        abort_if($service->customer_id != auth()->id(), 404);
        abort_if($service->state == 'pending', 404);
        $customer = $service->customer;
        $gateways = GatewayService::getAvailable();

        return view('front.provisioning.services.options', compact('service', 'customer', 'gateways'));
    }

    public function upgradeProcess(Service $service, Product $product)
    {
        abort_if($service->customer_id != auth()->id(), 404);
        abort_if($service->state == 'pending', 404);
        abort_if(! $service->canUpgrade(), 404);
        abort_if(! $product->isValid(), 404);
        if (! in_array($product->id, $service->product->getUpgradeProducts()->pluck('id')->toArray())) {
            return redirect()->route('front.services.upgrade', ['service' => $service->uuid])->with('error', __('client.alerts.service_upgrade_not_allowed'));
        }
        try {
            $invoice = InvoiceService::createInvoiceFromUpgrade($service, $product);
        } catch (\Exception $e) {
            return redirect()->route('front.services.upgrade', ['service' => $service->uuid])->with('error', $e->getMessage());
        }

        return redirect()->route('front.invoices.show', ['invoice' => $invoice->id]);
    }

    public function show(Service $service)
    {
        abort_if($service->customer_id != auth()->id(), 404);
        abort_if($service->state == 'pending', 404);
        $customer = $service->customer;
        $gateways = GatewayService::getAvailable();
        $panel_html = ProvisioningTabDTO::renderPanel($service);
        if ($service->hasMetadata('renewal_error')) {
            \Session::flash('error', __('client.services.subscription.failed', ['date' => $service->getMetadata('renewal_last_try'), 'tries' => $service->getMetadata('renewal_tries')]));
        }
        if ($service->invoice_id != null && $service->canRenew() && $service->getSubscription()->isActive()) {
            \Session::flash('warning', __('client.alerts.service_not_paid', ['url' => route('front.invoices.show', ['invoice' => $service->invoice])]));
        }
        if ($service->isCancelled() || $service->cancelled_at != null) {
            if ($service->cancelled_at->isPast()) {
                \Session::flash('warning', __('client.alerts.service_cancelled'));
            } else {
                \Session::flash('info', __('client.alerts.service_cancelled_and_not_expired'));
            }
        }
        if ($service->trial_ends_at != null && $service->trial_ends_at->isFuture()) {
            \Session::flash('info', __('client.alerts.service_trial_ends_at', ['date' => $service->trial_ends_at->format('d/m')]));
        }

        return view('front.provisioning.services.show', compact('service', 'customer', 'gateways', 'panel_html'));
    }

    public function renew(Request $request, Service $service, string $gateway)
    {
        if (! auth('web')->user()->hasServicePermission($service, 'service.renew')) {
            abort(404);
        }
        if (! $service->canRenew()) {
            return redirect()->route('front.services.show', ['service' => $service])->with('error', __('client.alerts.cannot_renew'));
        }
        $gateway = \App\Models\Billing\Gateway::getAvailable()->where('uuid', $gateway)->first();
        abort_if(! $gateway, 404);
        try {
            $invoice = ServiceService::createRenewalInvoice($service, $service->billing);
            return $invoice->pay($gateway, $request);
        } catch (WrongPaymentException $e) {
            logger()->error($e->getMessage());
            $message = __('store.checkout.wrong_payment');
            if (auth('admin')->check()) {
                $message .= ' Debug admin : '.$e->getMessage();
            }
            return back()->with('error', $message);
        }

    }

    public function tab(Service $service, string $tab)
    {
        abort_if($service->customer_id != auth()->id(), 404);
        $customer = $service->customer;
        $gateways = GatewayService::getAvailable();
        $panel = $service->productType()->panel();
        if ($panel == null) {
            return redirect()->route('front.services.show', ['service' => $service->uuid]);
        }
        $current_tab = $panel->getTab($service, $tab);

        if (! $current_tab) {
            return redirect()->route('admin.services.show', ['service' => $service])->with('error', __('provisioning.admin.services.tab_not_found'));
        }
        $panel_html = $current_tab->renderTab($service);
        if ($panel_html instanceof \Illuminate\Http\Response || $panel_html instanceof \Illuminate\Http\RedirectResponse) {
            return $panel_html;
        }
        return view('front.provisioning.services.show', compact('current_tab', 'panel_html', 'service', 'customer', 'gateways'));
    }

    public function renewal(Service $service)
    {
        if (! auth('web')->user()->hasServicePermission($service, 'service.renewal')) {
            abort(404);
        }
        $customer = $service->customer;
        $gateways = GatewayService::getAvailable();
        $renewals = $service->serviceRenewals()->whereNotNull('renewed_at')->orderBy('created_at', 'desc')->paginate(10);

        return view('front.provisioning.services.renewal', compact('gateways', 'service', 'customer', 'renewals'));
    }

    public function billing(Request $request, Service $service)
    {
        if (! auth('web')->user()->hasServicePermission($service, 'service.billing')) {
            abort(404);
        }
        $recurrings = collect($service->pricingAvailable($service->currency))->map(function ($recurring) {
            return $recurring->recurring;
        })->join(',');
        $gateways = Gateway::getAvailable();
        $this->validate($request, [
            'billing' => 'required|in:'.$recurrings,
            'gateway' => 'nullable|in:'.$gateways->pluck('uuid')->join(','),
        ]);
        $service->billing = $request->get('billing');
        event(new \App\Events\Core\Service\ServiceChangeBillingEvent($service, $request->get('billing')));
        $service->save();
        if ($request->has('pay')) {
            try {
                $invoice = ServiceService::createRenewalInvoice($service, $service->billing);
                $gateway = $gateways->where('uuid', $request->get('gateway'))->first();
                return $invoice->pay($gateway, $request);
            } catch (WrongPaymentException $e) {
                logger()->error($e->getMessage());
                $message = __('store.checkout.wrong_payment');
                if (auth('admin')->check()) {
                    $message .= ' Debug admin : '.$e->getMessage();
                }
                return back()->with('error', $message);
            }
        }

        return redirect()->route('front.services.show', ['service' => $service->uuid])->with('success', __('client.alerts.service_billing_updated'));
    }

    public function name(Request $request, Service $service)
    {
        if (! auth('web')->user()->hasServicePermission($service, 'service.name')) {
            abort(404);
        }
        $this->validate($request, [
            'name' => 'required|string|max:50',
        ]);
        $service->name = $request->get('name');
        event(new \App\Events\Core\Service\ServiceChangeNameEvent($service, $request->get('name')));
        $service->save();

        return redirect()->route('front.services.show', ['service' => $service->uuid])->with('success', __('client.alerts.service_name_updated'));
    }

    public function cancel(Request $request, Service $service)
    {

        if (! auth('web')->user()->hasServicePermission($service, 'service.cancel')) {
            abort(404);
        }
        if ($service->isOneTime()) {
            $request->request->set('expiration', 'now');
        }
        if ($service->cancelled_at != null) {
            $service->uncancel();
            return redirect()->route('front.services.show', ['service' => $service->uuid])->with('success', __('client.alerts.service_uncancelled'));
        }
        $request->validate([
            'reason' => ['required', 'string', 'exists:cancellation_reasons,id'],
            'details' => 'nullable|string',
            'expiration' => ['required', 'string', 'in:end_of_period,now'],
        ]);
        if (! $service->canCancel()) {
            return redirect()->route('front.services.show', ['service' => $service->uuid])->with('error', __('client.alerts.cannot_cancel'));
        }
        $reason = \App\Models\Provisioning\CancellationReason::find($request->reason)->reason;
        $reason = $reason.(! empty($request->details) ? ' - '.$request->details : '');
        $date = $request->expiration == 'end_of_period' ? $service->expires_at : new \DateTime;
        $service->cancel($reason, $date, $request->expiration == 'now');
        return redirect()->route('front.services.show', ['service' => $service->uuid])->with('success', __('client.alerts.service_cancelled'));
    }

    public function subscription(Request $request, Service $service)
    {
        if (! auth('web')->user()->hasServicePermission($service, 'service.subscription')) {
            abort(404);
        }
        if (! $service->canSubscribe()) {
            return back()->with('error', __('client.services.subscription.cannot'));
        }
        if (array_key_exists('cancel', $request->all())) {
            $service->subscription->cancel();
            return back()->with('success', __('client.services.subscription.cancelled', ['date' => $service->expires_at->format('d/m')]));
        }
        $paymentmethods = $service->customer->getPaymentMethodsArray(true)->keys()->join(',');
        if (empty($paymentmethods)) {
            return back()->with('error', __('client.services.subscription.cannot'));
        }
        $validated = $request->validate([
            'paymentmethod' => "in:$paymentmethods",
            'billing_day' => ['nullable', "between:1,28", new isValidBillingDayRule($service)]
        ]);
        $paymentmethod = $validated['paymentmethod'];
        $subscription = Subscription::createOrUpdateForService($service, $paymentmethod);
        if ($request->has('billing_day')){
            $billingDay = $request->get('billing_day');
            $subscription->setBillingDay($billingDay);
            return back()->with('success', __('client.services.subscription.billing_day_updated', ['date' => $subscription->getNextPaymentDate()]));
        }

        return back()->with('success', __('client.services.subscription.success', ['date' => $subscription->getNextPaymentDate()]));
    }

    public function getFilters()
    {
        return Service::FILTERS + Group::getAvailable()->whereNull('parent_id')->pluck('name', 'slug')->toArray();
    }
}
