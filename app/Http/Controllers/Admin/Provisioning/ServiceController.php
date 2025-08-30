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


namespace App\Http\Controllers\Admin\Provisioning;

use App\DTO\Admin\MassActionDTO;
use App\DTO\Provisioning\ProvisioningTabDTO;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\DTO\Store\ProductDataDTO;
use App\DTO\Store\UpgradeDTO;
use App\Exceptions\ExternalApiException;
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Http\Requests\Provisioning\StoreServiceRequest;
use App\Http\Requests\Provisioning\UpdateServiceRequest;
use App\Http\Requests\Provisioning\UpgradeServiceRequest;
use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceRenewals;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use App\Rules\isValidBillingDayRule;
use App\Services\Billing\InvoiceService;
use App\Services\Provisioning\ServiceService;
use App\Services\Store\PricingService;
use App\Services\Store\RecurringService;
use App\Traits\Controllers\ServiceControllerTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\QueryBuilder;

class ServiceController extends AbstractCrudController
{
    protected string $viewPath = 'admin.provisioning.services';

    protected string $routePath = 'admin.services';

    protected string $translatePrefix = 'provisioning.admin.services';

    protected string $model = Service::class;

    protected int $perPage = 25;

    protected string $searchField = 'id';

    protected array $filters = [
        'id',
        'customer_id',
        'name',
        'product_id',
    ];

    protected array $sorts = [
        'id',
        'customer_id',
        'status',
        'created_at',
    ];

    protected array $relations = ['customer', 'product'];

    use ServiceControllerTrait;

    public function getIndexFilters()
    {
        return collect(Service::FILTERS)->mapWithKeys(function ($k, $v) {
            return [$k => __('global.states.'.$v)];
        })->toArray();
    }

    protected function getMassActions()
    {
        return [
            new MassActionDTO('suspend', __('provisioning.admin.services.suspend.btn'), function (Service $service, ?string $reason = null) {
                return $service->suspend($reason ?? null);
            }, __('provisioning.admin.services.suspend.reason')),
            new MassActionDTO('unsuspend', __('provisioning.admin.services.unsuspend.btn'), function (Service $service) {
                return $service->unsuspend();
            }),
            new MassActionDTO('expire', __('provisioning.admin.services.terminate.btn'), function (Service $service) {
                return $service->expire();
            }),
            new MassActionDTO('cancel', __('provisioning.admin.services.cancel.btn'), function (Service $service, ?string $reason = null) {
                return $service->cancel($reason ?? 'Not specified', now(), true);
            }, __('client.services.cancel.reason')),
            new MassActionDTO('add_days', __('provisioning.admin.services.add_days'), function (Service $service, ?string $days = null) {
                return $service->addDays((int) $days);
            }, __('provisioning.admin.services.add_days_question')),

            new MassActionDTO('sub_days', __('provisioning.admin.services.sub_days'), function (Service $service, ?string $days = null) {
                return $service->addDays((int) $days);
            }, __('provisioning.admin.services.sub_days_question')),
            new MassActionDTO('deliver', __('provisioning.admin.services.delivery.btn'), function (Service $service) {
                return $service->deliver();
            }),
            new MassActionDTO('delete', __('global.delete'), function (Service $service) {
                return $service->delete();
            }),

        ];
    }

    protected function getSearchFields()
    {
        return [
            'customer.email' => __('global.customer'),
            'id' => 'Identifier',
            'uuid' => 'UUID',
            'name' => __('global.name'),
            'product_id' => __('global.product'),
        ];
    }

    public function show(Service $service)
    {
        $this->checkPermission('show', $service);
        $params['item'] = $service;
        $params['panel_html'] = ProvisioningTabDTO::renderPanel($service, true);
        $params['renewals'] = $service->serviceRenewals()->whereRaw('(renewed_at IS NOT NULL OR first_period = 1)')->orderBy('created_at', 'desc')->get();
        $params['products'] = $this->getProductsList();
        $params['invoices'] = $service->customer ? $service->customer->getPendingInvoices()->mapWithKeys(function (Invoice $invoice) {
            return [$invoice->id => __('global.invoice').' - '.$invoice->invoice_number];
        })->put('none', __('global.none')) : collect()->put('none', __('global.none'));
        $params['servers'] = $this->getServersList($service->type);
        $params['types'] = $this->getProductTypes();
        $params['paymentmethods'] = $service->customer ? $service->customer->getPaymentMethodsArray() : collect();
        $params['pricing'] = $service->getPricing();
        $params['recurrings'] = app(RecurringService::class)->getRecurrings();
        $params['upgrade_products'] = $service->product ? collect($service->product->getUpgradeProducts())->mapWithKeys(function (Product $product) use ($service) {
            $price = (new UpgradeDTO($service))->generatePrice($product);

            return [$product->id => $product->trans('name').' - '.$price->pricingMessage()];
        }) : collect();
        if ($service->delivery_errors != null && $service->status == Service::STATUS_PENDING) {
            \Session::flash('warning', $service->delivery_errors);
        }
        if ($service->hasMetadata('renewal_error')) {
            \Session::flash('error', sprintf('Renewal error (trial : %d) : %s', $service->getMetadata('renewal_tries'), $service->getMetadata('renewal_error')));
        }
        if ($service->isExpired()) {
            \Session::flash('warning', __('provisioning.admin.services.expired'));
        }
        if ($service->trial_ends_at != null && $service->trial_ends_at->isFuture()) {
            \Session::flash('info', __('client.alerts.service_trial_ends_at', ['date' => $service->trial_ends_at->format('d/m')]));
        }
        if ($service->isCancelled() || $service->cancelled_at != null) {
            if ($service->cancelled_at->isPast()) {
                \Session::flash('warning', __('client.alerts.service_cancelled'));
            } else {
                \Session::flash('info', __('client.alerts.service_cancelled_and_not_expired'));
            }
        }
        return $this->showView($params);
    }

    public function subscription(Request $request, Service $service)
    {
        abort_if(! staff_has_permission('admin.show_payment_methods'), 403);
        if (! $service->canSubscribe()) {
            return back()->with('error', __('client.services.subscription.cannot'));
        }
        if (array_key_exists('cancel', $request->all())) {
            $service->subscription->cancel();

            return back()->with('success', __('client.services.subscription.cancelled', ['date' => $service->expires_at->format('d/m')]));
        }
        $paymentmethods = $service->customer->getPaymentMethodsArray(true)->keys()->join(',');

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
        return redirect()->route($this->routePath.'.show', [$service])->with('success', __('client.services.subscription.success', ['date' => $subscription->getNextPaymentDate()->format('d/m')]));
    }

    public function upgrade(UpgradeServiceRequest $request, Service $service)
    {
        $this->checkPermission('show', $service);
        $validated = $request->validated();
        abort_if(! $service->canUpgrade(), 404);
        $product = Product::find($validated['product_id']);
        if (! in_array($product->id, $service->product->getUpgradeProducts()->pluck('id')->toArray())) {
            return redirect()->route('admin.services.show', ['service' => $service])->with('error', __('client.alerts.service_upgrade_not_allowed'));
        }
        try {
            $upgrade = ServiceService::upgradeService($service, $product, $validated['type']);
            if ($upgrade instanceof Invoice) {
                return redirect()->route('admin.invoices.show', ['invoice' => $upgrade->id]);
            } else {
                return redirect()->route('admin.services.show', ['service' => $service])->with('success', __($this->translatePrefix.'.upgrade.success'));
            }
        } catch (ExternalApiException $e) {
            return redirect()->route('admin.services.show', ['service' => $service])->with('error', $e->getMessage());
        }
    }

    public function create(Request $request)
    {
        $this->checkPermission('create');
        if ($request->query->has('customer_id') && $request->query->has('type') && $request->query->has('product_id')) {
            $types = app('extension')->getProductTypes()->keys()->merge(['none'])->toArray();
            $data = $request->only('customer_id', 'type', 'product_id');
            $data['product_id'] = ($data['product_id'] ?? 'none') == 'none' ? null : (int) $data['product_id'];
            $validator = \Validator::make($data, [
                'customer_id' => ['nullable', 'required', 'integer', Rule::exists('customers', 'id')],
                'type' => ['nullable', 'required', 'string', 'max:255', Rule::in($types)],
                'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            ]);
            $product = null;
            if ($data['product_id'] != null) {
                $product = Product::find($data['product_id']);
                if ($product->type != $data['type'] ?? 'none') {
                    return back()->with('error', __('provisioning.admin.services.invalid_product_type', ['product' => $product->type, 'type' => $data['type']]));
                }
                $price = $product->getFirstPrice();
                $data['name'] = $product->name;
                $data['currency'] = $price->currency;
                $data['billing'] = $price->recurring;
                $data['expires_at'] = app(RecurringService::class)->addFromNow($price->recurring);
            } else {
                $data['currency'] = currency();
                $data['billing'] = 'monthly';
                $data['expires_at'] = now()->addMonth();
            }
            if ($validator->fails()) {
                return back()->with('error', __('provisioning.admin.services.invalid_customer'));
            }
            $params['step'] = 2;
            $params['item'] = (new Service($data));
            $params['servers'] = $this->getServersList($data['type'] ?? 'none');
            $server = null;
            if ($product !== null) {
                $productServer = $product->productType()->server();
                if ($productServer !== null) {
                    $server = $productServer->findServer($product);
                }
                if ($server === null) {
                    $server = Server::getAvailable()->where('type', $data['type'])->first();
                }
            }
            if ($server != null) {
                $params['item']->fill(['server_id' => $server->id]);
            }
            $server = $params['item']->productType()->server();
            $params['importHTML'] = ($server != null && $server->importService()) ? $server->importService()->render($params['item'], $data) : null;
            $params['dataHTML'] = ($server != null && $params['item']->productType()->data($product ?? null) != null) ? $params['item']->productType()->data($product ?? null)->renderAdmin(new ProductDataDTO($params['item']->product ?? (new Product(['id' => -1])), $request->all() + ['in_admin' => true, 'service_creation' => true], [], [])) : null;
            $params['recurrings'] = app(RecurringService::class)->getRecurrings();
            $params['pricing'] = $params['item']->getPricing();
            $params['options'] = $product ? $product->configoptions()->orderBy('sort_order')->get() : collect();
            $params['options_html'] = collect($params['options'])->map(function ($product) {
                return $product->render([]);
            })->implode('');
            $params['options_prices'] = collect($params['options'])->mapWithKeys(function ($product) {
                return [$product->key => ['pricing' => $product->getPricingArray(), 'key' => $product->key, 'type' => $product->type, 'step' => $product->step, 'unit' => $product->unit, 'title' => $product->name]];
            });
            $params['customer_id'] = $request->get('customer_id');
            $params['item']->customer_id = $request->get('customer_id');
        } else {
            $params['item'] = (new Service([]));
            $params['products'] = $this->getProductsList();
            $params['types'] = $this->getProductTypes();
            $params['product_id'] = current($params['products']->keys());
            $params['step'] = 1;
            $params['dataHTML'] = null;
            $params['productTypes'] = Product::getAvailable(true)->pluck('type', 'id');
            $params['defaultCustomer'] = request()->query('customer_id');
            $params['item']->customer_id = $request->get('customer_id');
        }

        return $this->createView($params);
    }

    public function store(StoreServiceRequest $request)
    {
        $this->checkPermission('create');
        $service = Service::create($request->validated());
        $service->saveOptions($request->get('options', []) ?? []);
        if (array_key_exists('import', $request->all())) {
            return $this->import($request, $service);
        }
        if (array_key_exists('create', $request->all())) {
            return $this->createNew($request, $service);
        }
    }

    public function tab(Service $service, string $tab)
    {
        $panel = $service->productType()->panel();
        if ($panel == null) {
            return redirect()->route('admin.services.show', ['service' => $service]);
        }
        $current_tab = $panel->getTab($service, $tab);
        if (! $current_tab) {
            return redirect()->route('admin.services.show', ['service' => $service])->with('error', __('provisioning.admin.services.tab_not_found'));
        }
        $tab_html = $current_tab->renderTab($service, true);
        if ($tab_html instanceof \Illuminate\Http\Response || $tab_html instanceof \Illuminate\Http\RedirectResponse) {
            return $tab_html;
        }
        $params['item'] = $service;
        $params['panel_html'] = $tab_html;
        $params['renewals'] = $service->serviceRenewals()->whereRaw('(renewed_at IS NOT NULL OR first_period = 1)')->orderBy('created_at', 'desc')->get();
        $params['paymentmethods'] = $service->customer->getPaymentMethodsArray();
        $params['invoices'] = $service->customer->getPendingInvoices()->mapWithKeys(function (Invoice $invoice) {
            return [$invoice->id => __('global.invoice').' - '.$invoice->invoice_number];
        })->put('none', __('global.none'));
        $params['intab'] = true;

        $params['upgrade_products'] = $service->product ? collect($service->product->getUpgradeProducts())->mapWithKeys(function (Product $product) use ($service) {
            $price = (new UpgradeDTO($service))->generatePrice($product);

            return [$product->id => $product->trans('name').' - '.$price->pricingMessage()];
        }) : collect();

        return $this->showView($params);
    }

    public function updateData(Request $request, Service $service)
    {
        $this->checkPermission('update', $service);
        $validated = $request->validate([
            'data' => 'required|json|max:65535',
        ]);
        $service->data = json_decode($validated['data']);
        $service->save();

        return $this->updateRedirect($service);
    }

    public function renew(Request $request, Service $service)
    {
        $this->checkPermission('create_invoices', $service);
        if ($service->invoice_id != null) {
            ServiceRenewals::where('invoice_id', $service->invoice_id)->delete();
            $service->invoice->cancel();
            $service->invoice_id = null;
            $service->save();

            return back()->with('success', __('provisioning.admin.services.renewals.removed'));
        } else {
            try {
                $mode = $request->invoice_id == 'none' ? InvoiceService::CREATE_INVOICE : InvoiceService::APPEND_SERVICE;
                $invoice_id = $request->get('invoice_id') == 'none' ? null : $request->get('invoice_id');
                $invoice = ServiceService::createRenewalInvoice($service, $request->get('billing'), $mode, $invoice_id);
                $service->invoice_id = $invoice->id;
                $service->save();
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        return redirect()->route('admin.invoices.show', ['invoice' => $invoice->id]);
    }

    public function changeStatus(Request $request, Service $service, string $status)
    {
        $this->checkPermission('update', $service);
        if (! in_array($status, ['suspend', 'unsuspend', 'expire', 'cancel', 'cancel_delivery'])) {
            return back()->with('error', __('provisioning.admin.services.invalid_status'));
        }
        if ($status == 'suspend') {
            $request->validate([
                'reason' => 'nullable|string|max:255',
            ]);
        }
        [$result, $status] = ServiceService::changeServiceStatus($request, $service, $status);
        if ($result->success) {
            return back()->with('success', __('provisioning.admin.services.'.$status.'.success'));
        } else {
            return back()->with('error', __('provisioning.admin.services.status_change_failed', ['error' => $result->message]));
        }
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $this->checkPermission('update', $service);
        try {
            $service->fill($request->validated());
            $service->save();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
        if ($request->validated('resync') && $service->product_id != null) {
            Pricing::where('related_id', $service->id)->where('related_type', 'service')->delete();
            PricingService::forgot();

            return $this->updateRedirect($service);
        }
        if ($service->product_id != null) {
            Pricing::createOrUpdateIfChanged($request->validated(), $service->product_id, 'product', $service->id, 'service');
        } else {
            Pricing::createOrUpdateFromArray($request->validated(), $service->id, 'service');
        }
        PricingService::forgot();

        return $this->updateRedirect($service);
    }

    public function delivery(Service $service)
    {
        staff_aborts_permission('admin.deliver_services');
        if ($service->isPending()) {
            try {
                $result = $service->deliver();
                if ($result->success) {
                    return back()->with('success', __('provisioning.admin.services.delivery.success'));
                } else {
                    return back()->with('error', $result->message);
                }
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        return back()->with('error', __('provisioning.admin.services.delivery.not_pending'));
    }

    public function reinstall(Service $service): \Illuminate\Http\RedirectResponse
    {
        staff_aborts_permission('admin.deliver_services');
        if ($service->isPending()) {
            try {
                $result = $service->expire(true);
                if ($result->success) {
                    $service->status = Service::STATUS_PENDING;
                    $service->save();

                    return back()->with('info', __('provisioning.admin.services.delivery.reinstall'));
                } else {
                    $service->status = Service::STATUS_PENDING;
                    $service->save();

                    return back()->with('info', __('provisioning.admin.services.delivery.reinstall'))->with('error', $result->message);
                }
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        return back()->with('error', __('provisioning.admin.services.delivery.not_pending'));
    }

    public function search(Request $request)
    {
        $q = $request->get('q');
        if (filter_var($q, FILTER_VALIDATE_EMAIL)) {
            $customer = Customer::select('id')->where('email', $q)->first();
            if ($customer == null) {
                return $this->model::where('id', -1)->paginate($this->perPage);
            }

            return $this->model::where('customer_id', $customer->id)->paginate($this->perPage);
        }

        return $this->model::where('id', $q)->paginate($this->perPage);
    }

    public function destroy(Service $service)
    {
        $this->checkPermission('delete', $service);
        $result = $service->expire();
        $service->delete();
        if (! $result->success) {
            \Session::flash('error', __('provisioning.admin.services.delete_failed', ['error' => $result->message]));
        }

        return $this->deleteRedirect($service);
    }

    private function import(Request $request, Service $service)
    {
        $this->checkPermission('create');
        if ($service->productType()->server() != null && $service->productType()->server()->importService() != null) {
            $validator = \Validator::make($request->all(), $service->productType()->server()->importService()->validate());
            if ($validator->fails()) {
                return back()->with('error', implode('<br>', $validator->errors()->all()));
            }
            /** @var ServiceStateChangeDTO $result */
            $result = $service->productType()->server()->importService()->import($service, $validator->validated() + $request->validated());
            if ($result->success) {
                $service->delivery_errors = null;
                $service->status = Service::STATUS_ACTIVE;
                $service->save();
                if ($service->product_id != null) {
                    Pricing::createOrUpdateIfChanged($request->validated(), $service->product_id, 'product', $service->id, 'service');
                } else {
                    Pricing::createOrUpdateFromArray($request->validated(), $service->id, 'service');
                }
                PricingService::forgot();

                return redirect()->route('admin.services.show', ['service' => $service])->with('success', __('provisioning.admin.services.imported'));
            } else {
                return back()->with('error', $result->message);
            }
        }

        return redirect()->route('admin.services.show', ['service' => $service])->with('success', __('provisioning.admin.services.imported'));
    }

    private function createNew(StoreServiceRequest $request, Service $service)
    {
        $this->checkPermission('create');
        $service->attachMetadata('service_created_by', auth('admin')->id());
        $service->attachMetadata('service_created_at', now());
        $service->attachMetadata('must_created_manually', '1');
        $service->save();
        if ($service->product_id != null) {
            Pricing::createOrUpdateIfChanged($request->validated(), $service->product_id, 'product', $service->id, 'service');
        } else {
            Pricing::createOrUpdateFromArray($request->validated(), $service->id, 'service');
        }
        PricingService::forgot();
        if ($service->productType()->data($service->product ?? null) != null) {
            $validator = \Validator::make($request->all(), $service->productType()->data($service->product ?? null)->validate());
            if ($validator->fails()) {
                return back()->with('error', implode('<br>', $validator->errors()->all()));
            }
            $service->data = $service->productType()->data($service->product ?? null)->parameters(new ProductDataDTO($service->product ?? (new Product(['id' => -1])), $request->all() + ['in_admin' => true, 'service_creation' => true], $validator->validated()));
            $service->save();

            /** @var ServiceStateChangeDTO $result */
            try {
                $result = $service->deliver();
                if ($result->success) {
                    $service->delivery_errors = null;
                    $service->status = Service::STATUS_ACTIVE;
                    $service->save();
                }
            } catch (\Exception $e) {
                $service->delivery_errors = $e->getMessage();
                $service->status = Service::STATUS_PENDING;
                $service->save();
            }
        }

        return $this->storeRedirect($service)->with('success', __('provisioning.admin.services.create.created'));
    }

    protected function queryIndex(): LengthAwarePaginator
    {
        return QueryBuilder::for($this->model)
            ->allowedFilters(array_merge(array_keys($this->getSearchFields()), [$this->filterField, 'status']))
            ->allowedSorts($this->sorts)
            ->with($this->relations)
            ->when(! request()->has('filter.status'), function ($query) {
                $query->where('status', '!=', 'hidden');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage)
            ->appends(request()->query());
    }
}
