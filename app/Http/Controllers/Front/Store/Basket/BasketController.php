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

namespace App\Http\Controllers\Front\Store\Basket;

use App\DTO\Store\ProductDataDTO;
use App\Exceptions\WrongPaymentException;
use App\Helpers\Countries;
use App\Http\Requests\ProcessCheckoutRequest;
use App\Http\Requests\Store\Basket\BasketConfigRequest;
use App\Models\Billing\Gateway;
use App\Models\Store\Basket\Basket;
use App\Models\Store\Basket\BasketRow;
use App\Models\Store\Product;
use App\Contracts\Store\ProductTypeInterface;
use App\Services\Domain\DomainPricingService;
use App\Services\Account\AccountEditService;
use App\Services\Billing\InvoiceService;
use App\Services\Store\ProductConfigurationPricingService;
use Illuminate\Http\Request;

class BasketController extends \App\Http\Controllers\Controller
{
    public function addProduct(Request $request, Product $product)
    {
        if ($product->isNotValid(true)) {
            return back()->with('error', __('store.basket.not_valid'));
        }
        if ($product->hasPricesForCurrency() !== true) {
            return back()->with('error', __('store.basket.no_prices'));
        }
        if (! $product->canAddToBasket()) {
            return back()->with('error', __('store.basket.already_ordered', ['product' => $product->name]));
        }
        $basket = Basket::firstOrCreate([
            'user_id' => auth()->id(),
            'uuid' => Basket::getUUID(),
            'completed_at' => null,
        ]);

        return redirect()->to(route('front.store.basket.config', ['product' => $product]) . ($request->getQueryString() != null ? '?' . $request->getQueryString() : ''));
    }

    public function show(Request $request)
    {
        $basket = Basket::getBasket();
        if ($request->query('coupon') !== null) {
            $apply = $basket->applyCoupon($request->coupon);
            if ($apply === true) {
                return redirect()->route('front.store.basket.show')->with('success', __('coupon.coupon_applied'));
            }
        }
        $this->checkPrerequisites(false, $basket, 'front.store.basket.show');

        return view('front.store.basket.show', [
            'basket' => $basket,
        ]);
    }

    public function showConfigProduct(Product $product, Request $request)
    {
        if ($product->isNotValid(true)) {
            return back()->with('error', __('store.basket.not_valid'));
        }
        $row = BasketRow::findByProductOnSession($product, false);
        $available = $product->type === ProductTypeInterface::DOMAIN && $request->query('tld')
            ? app(DomainPricingService::class)->availableForTld($request->query('tld'), currency())
            : $product->pricingAvailable(currency());
        $validated = $request->validate([
            'billing' => 'nullable|string|in:' . implode(',', collect($available)->pluck('recurring')->toArray()),
        ]);
        $billing = $validated['billing'] ?? $row->billing;
        if ($product->getPriceByCurrency(currency(), $billing)->price == 0 && count($available) > 0) {
            $billing = $available[0]->recurring;
        }

        if (! $product->canAddToBasket()) {
            return back()->with('error', __('store.basket.already_ordered', ['product' => $product->name]));
        }
        $context = ['product' => $product, 'options' => [], 'billing' => $billing, 'row' => $row];
        if ($product->productType()->data($product) !== null) {
            $context['data_html'] = $product->productType()->data($product)->render(new ProductDataDTO($product, $row->data ?? [], $context['options'] ?? [], []));
        } else {
            $context['data_html'] = '';
        }
        $configoptions = $product->configoptions()->orderBy('sort_order')->get();
        $context['options_html'] = collect($configoptions)->map(function ($product) use ($row) {
            return $product->render($row->options ?? []);
        })->implode('');
        $context['options_prices'] = collect($configoptions)->mapWithKeys(function ($product) {
            return [$product->key => ['pricing' => $product->getPricingArray(), 'key' => $product->key, 'type' => $product->type, 'step' => $product->step, 'unit' => $product->unit, 'title' => $product->name]];
        });
        $context['options'] = $configoptions;

        return view('front.store.basket.config', $context);
    }

    public function configProduct(Product $product, BasketConfigRequest $request)
    {
        if (! $request->passes()) {
            return back()->with('error', collect($request->errors())->flatten()->values()->implode('<br>'));
        }
        $row = BasketRow::findByProductOnSession($product);
        if ($product->productType()->data($product) != null) {
            $data = $product->productType()->data($product)->parameters(new ProductDataDTO($product, $row->data ?? [], $request->validated())) + $request->validated();
        } else {
            $data = $request->validated();
        }
        unset($data['options']);
        if (array_key_exists('error', $data)) {
            return back()->with('error', $data['error']);
        }
        $row->billing = $request->billing;
        $row->currency = $request->currency;
        if ($product->isNotValid(true)) {
            return back()->with('error', __('store.basket.not_valid'));
        }
        if (! $product->canAddToBasket()) {
            return back()->with('error', __('store.basket.already_ordered', ['product' => $product->name]));
        }
        $row->data = $data;
        $row->saveOptions($request->validated()['options'] ?? [], $product->configoptions);
        $row->save();
        if ($request->has('coupon')) {
            $row->basket->applyCoupon($request->coupon);
        }

        return redirect()->route('front.store.basket.show')->with('success', __('store.basket.added'));
    }

    public function previewConfig(Product $product, BasketConfigRequest $request, ProductConfigurationPricingService $pricingService)
    {
        if ($product->isNotValid(true) || $product->hasPricesForCurrency() !== true) {
            return response()->json(['message' => __('store.basket.not_valid')], 422);
        }
        try {
            $validated = $request->validated();
            $errors = [];
        } catch (\Illuminate\Validation\ValidationException $e) {
            $validated = ['billing' => $request->billing, 'currency' => $request->currency];
            $errors = $e->errors();
        }
        if ($validated['billing'] == null || $validated['currency'] == null) {
            return response()->json(['message' => __('store.basket.validation_failed'), 'errors' => $errors], 422);
        }
        if (! $product->hasPricesForCurrency($validated['currency'])) {
            return response()->json(['message' => __('store.basket.no_prices')], 422);
        }

        // v2.16 — forward the basket-level coupon to the pricing service
        // so the customer sees the discounted total on the /basket/add
        // page, not only later at /store/basket.
        $coupon = null;
        $basket = Basket::getBasket();
        if ($basket->coupon_id !== null) {
            $coupon = \App\Models\Store\Coupon::find($basket->coupon_id);
        }

        $preview = $pricingService->preview(
            $product,
            $validated['billing'],
            $validated['currency'],
            $validated['options'] ?? [],
            $validated,
            $coupon,
        );

        return response()->json($preview + ['errors' => $errors]);
    }

    public function removeRow(Product $product)
    {
        $row = BasketRow::findByProductOnSession($product);
        $row->delete();

        return redirect()->route('front.store.basket.show')->with('success', __('store.basket.removed'));
    }

    public function changeQuantity(Product $product)
    {
        $row = BasketRow::findByProductOnSession($product);
        $row->quantity = request()->has('plus') ? $row->quantity + 1 : $row->quantity - 1;
        if ($row->quantity < 1 || $row->quantity > $product->stock || $product->isNotValid(true) || $row->quantity > 100) {
            $row->delete();

            return redirect()->route('front.store.basket.show')->with('success', __('store.basket.removed'));
        }
        if (! $row->canChangeQuantity()) {
            return redirect()->route('front.store.basket.show')->with('error', __('store.basket.already_ordered', ['product' => $product->name]));
        }
        $row->save();

        return redirect()->route('front.store.basket.show')->with('success', __('store.basket.quantity_changed'));
    }

    public function showCheckout()
    {
        $basket = Basket::getBasket();
        $this->checkPrerequisites(false, $basket, 'front.store.basket.show');

        if (app('extension')->extensionIsEnabled('socialauth')) {
            $providers = \App\Addons\SocialAuth\Models\ProviderEntity::where('enabled', true)->get();
        } else {
            $providers = collect([]);
        }

        return view('front.store.basket.checkout', [
            'basket' => $basket,
            'countries' => Countries::names(),
            'gateways' => \App\Models\Billing\Gateway::getAvailable()->get(),
            'providers' => $providers,
        ]);
    }

    public function processCheckout(ProcessCheckoutRequest $request)
    {
        $basket = Basket::getBasket();
        $prerequisite = $this->checkPrerequisites(false, $basket, 'front.store.basket.checkout');
        if ($prerequisite !== true) {
            return $prerequisite;
        }

        if ($basket->total() == 0) {
            $gateway = Gateway::where('uuid', 'none')->first();
        } else {
            /** @var Gateway|null $gateway */
            $gateway = \App\Models\Billing\Gateway::getAvailable()->where('uuid', $request->gateway)->first();
        }
        if ($gateway === null) {
            return redirect()->route('front.store.basket.checkout')->with('error', __('store.checkout.gateway_not_found'));
        }
        if ($gateway->minimal_amount > $basket->total()) {
            return redirect()->route('front.store.basket.checkout')->with('error', __('store.checkout.minimal_amount', ['amount' => formatted_price($gateway->minimal_amount)]));
        }
        AccountEditService::saveCurrentCustomer($request->validated());
        $invoice = InvoiceService::createInvoiceFromBasket($basket, $gateway);
        try {
            if ($request->has('paymentmethod') && $request->paymentmethod != null) {
                $source = auth('web')->user()->getSourceById($request->paymentmethod);
                $result = auth('web')->user()->payInvoiceWithPaymentMethod($invoice, $source);
                if (! $result->success) {
                    return redirect()->route('front.store.basket.checkout')->with('error', __('client.alerts.invoice_payment_failed'));
                } else {
                    $invoice->update(['payment_method_id' => $source->id, 'paymethod' => $source->gateway_uuid]);

                    return redirect()->route('front.invoices.show', $invoice)->with('success', __('admin.invoices.paidsuccess'));
                }
            }

            return $invoice->pay($gateway, $request);
        } catch (WrongPaymentException $e) {
            logger()->error($e->getMessage());
            $message = __('store.checkout.wrong_payment');
            if (auth('admin')->check()) {
                $message .= ' Debug admin : ' . $e->getMessage();
            }

            return redirect()->route('front.store.basket.checkout')->with('error', $message);
        }
    }

    public function coupon(Request $request)
    {
        $this->validate($request, [
            'coupon' => 'required|string|max:255',
            // v2.16 — accept an optional return URL so the customer
            // who applied the coupon from /basket/config/{product}
            // stays on that page instead of being yanked to
            // /store/basket.
            'redirect_to' => 'nullable|string|max:1000',
        ]);
        $basket = Basket::getBasket();
        $apply = $basket->applyCoupon($request->coupon);

        $target = $this->resolveCouponRedirect($request->input('redirect_to'));

        if ($apply === true) {
            return redirect($target)->with('success', __('coupon.coupon_applied'));
        }

        return redirect($target);
    }

    public function removeCoupon(Request $request)
    {
        $basket = Basket::getBasket();
        $basket->update(['coupon_id' => null]);

        $target = $this->resolveCouponRedirect($request->input('redirect_to'));

        return redirect($target)->with('success', __('coupon.coupon_removed'));
    }

    /**
     * v2.16 — Whitelist the only allowed redirect destinations after a
     * coupon apply/remove. Anything else falls back to the default
     * /store/basket page. Prevents open redirects while still letting
     * the basket-config page get the user back.
     */
    private function resolveCouponRedirect(?string $redirectTo): string
    {
        $default = route('front.store.basket.show');
        if ($redirectTo === null || $redirectTo === '') {
            return $default;
        }
        // Same-host paths only (must start with our APP_URL or be a
        // relative URL beginning with /). Drop any query string we
        // don't recognise just in case.
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $parsed = parse_url($redirectTo);
        $host = $parsed['host'] ?? null;
        if ($host !== null && $host !== $appHost) {
            return $default;
        }
        // Reject path traversal sequences before prefix-matching: /basket/../admin
        // would pass str_starts_with but the browser resolves it to /admin.
        $path = $parsed['path'] ?? '';
        if (str_contains($path, '..')) {
            return $default;
        }
        if (! str_starts_with($path, '/store/basket') && ! str_starts_with($path, '/basket')) {
            return $default;
        }
        return $redirectTo;
    }

    private function checkPrerequisites(bool $flash, Basket $basket, string $route)
    {
        if (! $basket->checkValid()) {
            if ($flash) {
                session()->flash('warning', __('store.checkout.invalidproduct'));
            }

            return redirect()->route($route)->with('warning', __('store.checkout.invalidproduct'));
        }
        if (! $basket->checkCurrency()) {
            if ($flash) {
                session()->flash('warning', __('store.checkout.invalidcurrency'));
            }

            return redirect()->route($route)->with('warning', __('store.checkout.invalidcurrency'));
        }
        if (auth('web')->guest()) {
            if ($flash) {
                session()->flash('warning', __('store.checkout.mustbelogged'));
            }

            return redirect()->route($route)->with('warning', __('store.checkout.mustbelogged'));
        }
        if (auth('web')->user() && auth('web')->user()->hasVerifiedEmail() !== true && setting('checkout.customermustbeconfirmed', false) === true) {
            if ($flash) {
                session()->flash('warning', __('store.checkout.mustbeconfirmed'));
            }

            return redirect()->route($route)->with('warning', __('store.checkout.mustbeconfirmed'));
        }
        if ($basket->rows->count() == 0) {
            if ($flash) {
                session()->flash('warning', __('store.checkout.empty'));
            }

            return redirect()->route($route)->with('warning', __('store.basket.empty'));
        }

        return true;
    }
}
