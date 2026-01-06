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

use App\DTO\Admin\MassActionDTO;
use App\DTO\Store\ProductDataDTO;
use App\Events\Core\Invoice\InvoiceCreated;
use App\Helpers\Countries;
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Http\Requests\Billing\ExportInvoiceRequest;
use App\Http\Requests\Billing\InvoiceDraftRequest;
use App\Http\Requests\Billing\StoreInvoiceRequest;
use App\Http\Requests\Billing\UpdateInvoiceRequest;
use App\Models\Account\Customer;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Provisioning\Service;
use App\Models\Store\Coupon;
use App\Models\Store\Product;
use App\Services\Billing\InvoiceService;
use App\Services\InvoiceExporterService;
use App\Services\Store\RecurringService;
use Illuminate\Http\Request;

class InvoiceController extends AbstractCrudController
{
    protected string $viewPath = 'admin.core.invoices';

    protected string $routePath = 'admin.invoices';

    protected string $translatePrefix = 'admin.invoices';

    protected string $model = Invoice::class;

    protected int $perPage = 25;

    protected string $searchField = 'email';

    protected array $relations = ['customer', 'gateway'];

    public function getIndexFilters()
    {
        return collect(Invoice::FILTERS)->merge([Invoice::STATUS_DRAFT => Invoice::STATUS_DRAFT])->mapWithKeys(function ($k, $v) {
            return [$k => __('global.states.' . $v)];
        })->toArray();
    }

    public function getSearchFields()
    {
        return [
            'id' => 'ID',
            'customer.email' => __('global.customer'),
            'invoice_number' => __('admin.invoices.invoice_number'),
            'external_id' => __('admin.invoices.show.external_id'),
            'uuid' => 'UUID',
        ];
    }

    public function getIndexParams($items, string $translatePrefix)
    {
        $params = parent::getIndexParams($items, $translatePrefix);
        $params['exportFormats'] = InvoiceExporterService::getAvailableFormats();

        return $params;
    }

    public function getMassActions()
    {
        return [
            new MassActionDTO('delete', __('global.delete'), function (Invoice $invoice) {
                if (! $invoice->canDelete()) {
                    return;
                }
                $invoice->items()->delete();
                $invoice->delete();
            }),
            new MassActionDTO('complete', __('admin.invoices.mass.complete'), function (Invoice $invoice) {
                $invoice->complete(false);
            }),
            new MassActionDTO('cancel', __('admin.invoices.mass.cancel'), function (Invoice $invoice) {
                $invoice->cancel();
            }),
            new MassActionDTO('refund', __('admin.invoices.mass.refund'), function (Invoice $invoice) {
                $invoice->refund();
            }),
            new MassActionDTO('fail', __('admin.invoices.mass.fail'), function (Invoice $invoice) {
                $invoice->fail();
            }),
        ];
    }

    public function store(StoreInvoiceRequest $request)
    {
        $this->checkPermission('create');
        $validatedData = $request->validated();
        $invoice = InvoiceService::createFreshInvoice($validatedData['customer_id'], $validatedData['currency'], 'Created manually by ' . auth('admin')->user()->username);

        return $this->storeRedirect($invoice);
    }

    public function deliver(Invoice $invoice, InvoiceItem $invoiceItem)
    {
        try {
            $invoiceItem->tryDeliver();

            return back()->with('success', __('admin.invoices.deliveredsuccess'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function draft(Invoice $invoice, InvoiceDraftRequest $request)
    {
        $this->checkPermission('update');
        $validatedData = $request->validated();
        $related = $validatedData['related'];
        $relatedId = $validatedData['related_id'];
        if ($related == 'service') {
            $service = Service::find($relatedId);
            $invoice->addService($service);
        } elseif ($related == 'product') {
            $product = Product::find($relatedId);
            if ($product->productType()->data($product) != null) {
                $productData = $product->productType()->data($product)->parameters(new ProductDataDTO($product, [], $validatedData)) + $validatedData;
            } else {
                $productData = $validatedData;
            }
            unset($productData['options']);
            if (array_key_exists('error', $productData)) {
                return back()->with('error', $productData['error']);
            }
            $productData['options'] = $this->processConfigOptions($product, $validatedData['options'] ?? []);
            $invoice->addProduct($product, $validatedData, $productData);
        } else {
            $validatedData['description'] = $validatedData['description'] ?? '';
            $invoice->addCustomProduct($validatedData);
        }
        $invoice->recalculate();

        return back()->with('success', __('admin.invoices.draft.itemadded'));
    }

    public function deleteItem(Invoice $invoice, InvoiceItem $invoiceItem)
    {
        $this->checkPermission('update');
        if (! $invoice->isDraft()) {
            return back()->with('error', __('admin.invoices.draft.notallowed'));
        }
        $invoiceItem->delete();
        $invoice->recalculate();

        return back()->with('success', __('admin.invoices.draft.itemremoved'));
    }

    public function cancelItem(Invoice $invoice, InvoiceItem $invoiceItem)
    {
        $this->checkPermission('update');
        $invoiceItem->cancel();
        $invoice->recalculate();

        return back()->with('success', __('admin.invoices.itemcancelled'));
    }

    public function updateItem(Invoice $invoice, InvoiceItem $invoiceItem, Request $request)
    {
        $this->checkPermission('update');
        if (! $invoice->isDraft()) {
            return back()->with('error', __('admin.invoices.draft.notallowed'));
        }
        $rules = [
            'quantity' => 'required|integer|min:1',
            'unit_price_ttc' => 'required|numeric|min:0',
            'unit_price_ht' => 'required|numeric|min:0',
            'unit_setup_ht' => 'required|numeric|min:0',
            'unit_setup_ttc' => 'required|numeric|min:0',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'coupon_id' => 'nullable',
            'billing' => 'nullable|string|in:' . implode(',', app(RecurringService::class)->getRecurringTypes()),
        ];

        $product = null;
        if ($invoiceItem->relatedType() instanceof Product) {
            $product = $invoiceItem->relatedType();
            $productType = $product->productType();
            if ($productType->data($product) !== null) {
                $rules = array_merge($rules, $productType->data($product)->validate());
            }
            $configOptions = $product->configoptions()->orderBy('sort_order')->get();
            foreach ($configOptions as $configOption) {
                $rules['options.' . $configOption->key] = $configOption->validate();
            }
        }

        $validatedData = $request->validate($rules);

        if ($product !== null) {
            if ($product->productType()->data($product) != null) {
                $data = $product->productType()->data($product)->parameters(new ProductDataDTO($product, $invoiceItem->data, $validatedData)) + $validatedData;
            } else {
                $data = $validatedData;
            }
            unset($data['options']);
            if (array_key_exists('error', $data)) {
                return back()->with('error', $data['error']);
            }
            $data['options'] = $this->processConfigOptions($product, $validatedData['options'] ?? []);
            $validatedData['data'] = $data;
        } else {
            $validatedData['data'] = [];
        }

        if (array_key_exists('billing', $validatedData)) {
            $validatedData['data']['billing'] = $validatedData['billing'];
        }
        $validatedData['description'] = $validatedData['description'] ?? '';
        if (array_key_exists('coupon_id', $validatedData)) {
            /** @var Coupon $coupon */
            $coupon = Coupon::find($validatedData['coupon_id']);
            if ($coupon != null) {
                $validatedData['discount'] = $coupon->discountArray($validatedData['unit_price_ht'], $validatedData['unit_setup_ht'], $invoiceItem->billing());
            } else {
                $validatedData['discount'] = [];
            }
        } else {
            $validatedData['discount'] = [];
        }
        $invoiceItem->update($validatedData);
        $invoiceItem->invoice->recalculate();

        return back()->with('success', __('admin.invoices.draft.itemupdated'));
    }

    public function show(Invoice $invoice)
    {
        $this->checkPermission('show');
        $invoice->load(['logs' => function ($query) {
            $query->with(['staff', 'customer'])->latest();
        }]);
        $params['item'] = $invoice;
        $params['invoice'] = $invoice;
        $params['customer'] = $invoice->customer;
        $params['address'] = $invoice->billing_address;
        $params['gateways'] = $this->gateways();
        $params['countries'] = Countries::names();
        $params['logs'] = $invoice->logs;
        if ($invoice->isDraft()) {
            $params['products'] = $this->products($invoice);
            $params['coupons'] = $this->coupons();
        }

        return $this->showView($params);
    }

    public function config(Invoice $invoice, Request $request)
    {
        $this->checkPermission('update');
        $relatedId = $request->get('related_id');
        $related = $request->get('related');
        $routePath = $this->routePath;
        $translatePrefix = $this->translatePrefix;
        $billing = 'monthly';
        if ($related == 'service') {
            /** @var Product|null $product */
            $service = Service::find($relatedId);
            $product = $service->product;
            $dataHTML = '';
        } elseif ($related == 'product') {
            /** @var Product|null $product */
            $product = Product::find($relatedId);
            $service = null;
            if ($product != null && $product->productType()->data($product) != null) {
                $dataHTML = $product->productType()->data($product)->renderAdmin(new ProductDataDTO($product, $request->all() + ['in_admin' => true], [], []));
            } else {
                $dataHTML = '';
            }
        } else {
            // Autre cas de figures
            $product = null;
            $service = null;
            $dataHTML = '';
        }
        if ($product != null) {
            $available = $product->pricingAvailable(currency());
            if ($product->getPriceByCurrency(currency(), $billing)->price == 0 && count($available) > 0) {
                $billing = $available[0]->recurring;
            }
        }
        $coupons = $this->coupons();

        return view($this->viewPath . '.config', compact('coupons', 'relatedId', 'related', 'service', 'billing', 'invoice', 'translatePrefix', 'routePath', 'product', 'dataHTML'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $this->checkPermission('update');
        $request->update($invoice);

        return $this->updateRedirect($invoice);
    }

    public function search(Request $request)
    {
        $q = $request->get('q');
        if (filter_var($q, FILTER_VALIDATE_EMAIL)) {
            $customer = Customer::select('id')->where('email', $q)->first();

            return $this->model::where('customer_id', $customer->id)->paginate($this->perPage);
        }

        return $this->model::where('id', $q)->paginate($this->perPage);
    }

    public function pdf(Invoice $invoice)
    {
        $this->checkPermission('show');

        return $invoice->pdf();
    }

    public function getCreateParams()
    {
        $params = parent::getCreateParams();
        $request = request();
        $defaultCustomer = null;
        if ($request->query('customer_id')) {
            $defaultCustomer = $request->query('customer_id');
        }
        $params['customers'] = $this->customers();
        $params['currencies'] = collect(currencies())->mapWithKeys(function ($currency) {
            return [$currency['code'] => $currency['code']];
        })->toArray();
        $params['defaultCustomer'] = $defaultCustomer;
        $params['item']->customer_id = $defaultCustomer;

        return $params;
    }

    public function destroy(Invoice $invoice)
    {
        $this->checkPermission('delete');
        abort_if(! $invoice->canDelete(), 404);
        $invoice->items()->delete();
        $invoice->delete();

        return $this->deleteRedirect($invoice);
    }

    public function export(ExportInvoiceRequest $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $this->checkPermission('export');

        return $request->export();
    }

    public function payInvoice(Invoice $invoice, Request $request)
    {
        $this->checkPermission('update');
        $validatedData = $request->validate([
            'source' => 'required|string|max:255',
        ]);
        $source = $validatedData['source'];
        try {
            $source = $invoice->customer->getSourceById($source);
            $result = $invoice->customer->payInvoiceWithPaymentMethod($invoice, $source);
            if ($result->success) {
                $result->invoice->update(['paymethod' => $source->gateway_uuid, 'payment_method_id' => $source->id]);

                return back()->with('success', __('admin.invoices.paidsuccess'));
            } else {
                return back()->with('error', $result->message);
            }
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function validateInvoice(Invoice $invoice)
    {
        $this->checkPermission('create');
        if ($invoice->status != Invoice::STATUS_DRAFT) {
            return back()->with('error', __('admin.invoices.draft.not_in_draft'));
        }
        if ($invoice->items->count() == 0) {
            return back()->with('error', __('admin.invoices.draft.empty'));
        }
        $invoice->status = Invoice::STATUS_PENDING;
        $invoice->save();
        event(new InvoiceCreated($invoice));

        return back()->with('success', __('admin.invoices.draft.validated'));
    }

    public function editInvoice(Invoice $invoice)
    {
        $this->checkPermission('create');
        if ($invoice->status != Invoice::STATUS_PENDING) {
            return back()->with('error', __('admin.invoices.draft.not_in_pending'));
        }
        $invoice->status = Invoice::STATUS_DRAFT;
        $invoice->save();

        return back()->with('success', __('admin.invoices.draft.set_in_draft'));
    }

    public function notify(Invoice $invoice)
    {
        $this->checkPermission('show');
        $invoice->notifyCustomer();

        return back()->with('success', __('admin.invoices.notified'));
    }

    private function customers()
    {
        return Customer::select(['id', 'email', 'firstname', 'lastname'])->get()->mapWithKeys(function (Customer $customer) {
            return [$customer->id => $customer->email];
        });
    }

    private function products(Invoice $invoice)
    {
        $products = Product::getAvailable(true)->pluck('name', 'id')->mapWithKeys(function ($name, $id) {
            return ['product-' . $id => $name];
        });
        foreach (Service::where('customer_id', $invoice->customer_id)->whereNotNull('expires_at')->get() as $service) {
            $products->put('service-' . $service->id, ' #' . $service->id . ' ' . $service->getInvoiceName());
        }
        $products->put('product-none', __('admin.invoices.customproduct'));

        return $products;
    }

    private function gateways()
    {
        return Gateway::getAvailable(true)->pluck('name', 'uuid')->mapWithKeys(function ($name, $uuid) {
            return [$uuid => $name];
        })->toArray();
    }

    private function coupons()
    {
        $coupons = Coupon::all();
        $coupons = $coupons->pluck('code', 'id')->mapWithKeys(function ($name, $id) {
            return [$id => $name];
        });
        $coupons->put('none', __('global.none'));

        return $coupons;
    }

    /**
     * Process config options similar to BasketRow::saveOptions
     */
    private function processConfigOptions(Product $product, array $options): array
    {
        $configOptions = $product->configoptions()->orderBy('sort_order')->get();
        $keys = $configOptions->pluck('key')->toArray();
        $saved = [];
        foreach ($options as $key => $value) {
            if ($value === null || ! in_array($key, $keys) || $value === '' || $value === '0') {
                unset($saved[$key]);
            } else {
                $saved[$key] = $value;
            }
        }

        return $saved;
    }
}
