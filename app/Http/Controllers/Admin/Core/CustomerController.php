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

namespace App\Http\Controllers\Admin\Core;

use App\Addons\SupportID\SupportIdHelper;
use App\Helpers\Countries;
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Http\Requests\Billing\ExportInvoiceRequest;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountAccess;
use App\Models\Billing\Invoice;
use App\Models\Helpdesk\SupportTicket;
use App\Models\Provisioning\Service;
use App\Providers\RouteServiceProvider;
use App\Services\Billing\FiscalProfileService;
use App\Services\Billing\InvoiceFilterService;
use App\Services\InvoiceExporterService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\QueryBuilder;

class CustomerController extends AbstractCrudController
{
    protected string $viewPath = 'admin.core.customers';

    protected string $routePath = 'admin.customers';

    protected string $translatePrefix = 'admin.customers';

    protected string $model = \App\Models\Account\Customer::class;

    protected int $perPage = 25;

    protected string $searchField = 'email';

    protected string $filterField = 'is_confirmed';

    public function getCreateParams()
    {
        $data = parent::getCreateParams();
        $data['countries'] = Countries::names();
        $data['locales'] = \App\Services\Core\LocaleService::getLocalesNames();

        return $data;
    }

    public function getIndexFilters()
    {
        return [];
    }

    public function getSearchFields()
    {
        $fields = [
            'email' => __('global.email'),
            'id' => 'ID',
            'firstname' => __('global.firstname'),
            'lastname' => __('global.lastname'),
            'phone' => __('global.phone'),
        ];
        if (app('extension')->extensionIsEnabled('supportid') && \Schema::hasColumn('customers', 'support_id')) {
            $fields['support_id'] = __('supportid::lang.admin.search.label');
        }

        return $fields;
    }

    public function show(Customer $customer, InvoiceFilterService $invoiceFilterService)
    {
        $this->checkPermission('show', $customer);
        request()->validate([
            'invoice_date_from' => 'nullable|date|before_or_equal:invoice_date_to',
            'invoice_date_to' => 'nullable|date|after_or_equal:invoice_date_from',
            'invoice_status' => 'nullable|array',
            'invoice_status.*' => 'string|in:'.implode(',', array_keys(Invoice::FILTERS + [Invoice::STATUS_DRAFT => Invoice::STATUS_DRAFT])),
            'invoice_currency' => 'nullable|string|size:3',
        ]);
        $params['item'] = $customer;
        $params['countries'] = Countries::names();
        $invoiceFilters = array_filter([
            'date_from' => request('invoice_date_from'),
            'date_to' => request('invoice_date_to'),
            'status' => request('invoice_status', []),
            'currency' => request('invoice_currency'),
        ]);
        $params['invoices'] = $invoiceFilterService->apply(Invoice::query(), $invoiceFilters)
            ->where(function ($query) {
                if (! request()->has('invoice_status')) {
                    $query->where('status', '!=', 'hidden');
                }
            })
            ->where('customer_id', $customer->id)
            ->orderBy('id', 'desc')
            ->paginate(20, ['*'], 'invoices')
            ->appends(request()->query());
        $params['invoiceFilters'] = $invoiceFilters;
        $params['invoiceCurrencies'] = $customer->invoices()->whereNotNull('currency')->distinct()->orderBy('currency')->pluck('currency');
        $params['services'] = QueryBuilder::for(Service::class)
            ->allowedFilters(['status'])
            ->where(function ($query) {
                if (! request()->has('filter.status')) {
                    $query->where('status', '!=', 'hidden');
                }
            })
            ->where('customer_id', $customer->id)
            ->orderBy('id', 'desc')
            ->paginate(20, ['*'], 'services')
            ->appends(\request()->query());
        $params['emails'] = $customer->emails()->orderBy('id', 'desc')->paginate(20, ['*'], 'emails');
        $params['tickets'] = QueryBuilder::for(SupportTicket::class)->where('customer_id', $customer->id)->allowedFilters(['status'])->orderBy('id', 'desc')->paginate(20, ['*'], 'tickets')->appends(\request()->query());
        $params['locales'] = \App\Services\Core\LocaleService::getLocalesNames();
        $params['logs'] = $customer->getLogsAction()->orderBy('id')->paginate(20, ['*'], 'logs');
        $currentPage = LengthAwarePaginator::resolveCurrentPage('paymentmethods');
        $params['checkedFilters'] = $this->getCheckedFilters();
        $params['paymentmethods'] = new LengthAwarePaginator($customer->paymentMethods(), $customer->paymentMethods()->count(), 20, $currentPage, ['path' => '', 'pageName' => 'paymentmethods']);
        $params['customerNotes'] = $customer->customerNotes()->orderBy('created_at', 'desc')->get();
        $params['accountAccesses'] = $customer->ownedAccountAccesses()->with(['subCustomer', 'services'])->orderBy('created_at', 'desc')->get();
        $params['accountInvitations'] = $customer->pendingAccountInvitations()->with('services')->orderBy('created_at', 'desc')->get();
        $params['accountAccessPermissions'] = [
            'services' => CustomerAccountAccess::SERVICE_PERMISSIONS,
            'invoices' => CustomerAccountAccess::INVOICE_PERMISSIONS,
        ];

        $params['creditNotes'] = $customer->creditNotes()->orderBy('id', 'desc')->paginate(20, ['*'], 'credit_notes')->appends(request()->query());
        $params['invoices_list'] = $customer->invoices()->orderBy('id', 'desc')->get();

        return $this->showView($params);
    }

    public function exportInvoices(ExportInvoiceRequest $request, Customer $customer, InvoiceFilterService $invoiceFilterService)
    {
        abort_unless(staff_has_permission('admin.export_invoices'), 403);
        $filters = $request->safe()->only(['date_from', 'date_to', 'status', 'currency']);
        $invoices = $invoiceFilterService->apply(
            Invoice::query()->where('customer_id', $customer->id),
            $filters
        )->orderBy('created_at')->get();
        if ($invoices->isEmpty()) {
            return back()->with('error', __('global.no_results'));
        }

        $path = InvoiceExporterService::exportInvoices($invoices, $request->validated('format'));

        return response()->download($path)->deleteFileAfterSend(true);
    }

    private function getCheckedFilters()
    {
        $filters = \request()->query('filter', []);
        if (! is_array($filters)) {
            $filters = [$this->filterField => $filters];
        }
        $checkedFilters = [];
        $statesTranslations = trans('global.states');
        $values = is_array($statesTranslations) ? array_keys($statesTranslations) : [];
        foreach ($filters as $field => $value) {
            $_values = explode(',', $value);
            foreach ($_values as $_value) {
                if (in_array($_value, $values)) {
                    $checkedFilters[] = $_value;
                }
            }
        }

        return $checkedFilters;
    }

    public function confirm(Customer $customer)
    {
        $this->checkPermission('update', $customer);
        if ($customer->hasVerifiedEmail()) {
            return redirect()->back()->with('error', __($this->translatePrefix.'.show.email_already_confirmed'));
        }
        $customer->markEmailAsVerified();
        $customer->save();

        return redirect()->back()->with('success', __($this->translatePrefix.'.show.email_confirmed'));
    }

    public function sendForgotPassword(Customer $customer)
    {
        $this->checkPermission('update', $customer);
        Password::broker('users')->sendResetLink($customer->only('email'));

        return redirect()->back()->with('success', __($this->translatePrefix.'.show.password_reset_sent'));
    }

    public function resendConfirmation(Customer $customer)
    {
        $this->checkPermission('update', $customer);
        if ($customer->hasVerifiedEmail()) {
            return redirect()->back()->with('error', __($this->translatePrefix.'.show.email_already_confirmed'));
        }
        $customer->sendEmailVerificationNotification();

        return redirect()->back()->with('success', __($this->translatePrefix.'.show.email_sent'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->checkPermission('update', $customer);
        $data = $request->validated();
        if ($request->has('password') && $request->password != '') {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }
        $customer->update($data);

        return $this->updateRedirect($customer);
    }

    public function updateFiscalProfile(Request $request, Customer $customer, FiscalProfileService $service)
    {
        $this->checkPermission('update', $customer);
        try {
            $service->update($customer, $request->all());
        } catch (ValidationException $exception) {
            return redirect()->route('admin.customers.show', ['customer' => $customer, 'tab' => 'einvoicing'])
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect()->route('admin.customers.show', ['customer' => $customer, 'tab' => 'einvoicing'])
            ->with('success', __('einvoicing.profile.saved'));
    }

    public function autologin(Customer $customer)
    {
        $this->checkPermission('admin.autologin_customer', $customer);
        \Session::put('autologin', true);
        \Session::put('autologin_customer', $customer->id);
        auth('web')->loginUsingId($customer->id);
        \Session::regenerate();
        \Session::forget(['2fa_verified', '2fa_secret']);
        \Session::flash('success', __('admin.customers.autologin.success', ['name' => e($customer->fullName)]));

        return redirect()->to(RouteServiceProvider::HOME);
    }

    public function logout()
    {
        $this->checkPermission('admin.autologin_customer');
        auth('web')->logout();
        $customer = Customer::find(\Session::get('autologin_customer'));
        \Session::remove('autologin');
        \Session::remove('autologin_customer');
        \Session::regenerate();
        \Session::forget(['2fa_verified', '2fa_secret']);
        \Session::flash('success', __('admin.customers.autologin.logoutsuccess', ['name' => e($customer->fullName)]));

        return redirect()->route('admin.customers.show', $customer);
    }

    public function destroy(Request $request, Customer $customer)
    {
        $this->checkPermission('delete', $customer);

        $deletionService = new \App\Services\Account\AccountDeletionService;
        $force = $request->boolean('force', false);

        try {
            $deletionService->delete($customer, $force);

            return $this->deleteRedirect($customer);
        } catch (\App\Services\Account\AccountDeletionException $e) {
            \Session::flash('error', $e->getFormattedReasons());

            return redirect()->back();
        }
    }

    public function store(StoreCustomerRequest $request)
    {
        $this->checkPermission('create');
        $customer = $request->store();

        return $this->storeRedirect($customer);
    }

    public function search(Request $request)
    {
        if (in_array('field', array_keys($request->all()))) {
            if (in_array($request->get('field'), ['id', 'email', 'firstname', 'lastname', 'phone'])) {
                $this->searchField = $request->get('field');
                if ($request->get('field') == 'id') {
                    $customer = Customer::find($request->get('q'));

                    return collect([$customer]);
                }
            }
            if ($request->get('field') == 'service_id') {
                staff_aborts_permission(\App\Models\Admin\Permission::SHOW_SERVICES);
                $service = \App\Models\Provisioning\Service::where('id', (int) $request->get('q'))->first();
                if ($service) {
                    $this->routePath = 'admin.services';

                    return collect([$service]);
                }
            }
            if ($request->get('field') == 'invoice_id') {
                staff_aborts_permission(\App\Models\Admin\Permission::SHOW_INVOICES);
                $invoice = \App\Models\Billing\Invoice::where('id', (int) $request->get('q'))->first();
                if ($invoice) {
                    $this->routePath = 'admin.invoices';

                    return collect([$invoice]);
                }
            }
            if (
                $request->get('field') == 'support_id'
                && app('extension')->extensionIsEnabled('supportid')
                && \Schema::hasColumn('customers', 'support_id')
            ) {
                $customer = Customer::where(
                    'support_id',
                    SupportIdHelper::normalize($request->get('q'))
                )->first();
                if ($customer) {
                    $this->routePath = 'admin.customers';

                    return collect([$customer]);
                }
            }
        }

        return parent::search($request);
    }

    public function customSearch(Request $request)
    {
        $this->checkPermission('showAny');
        $q = $request->get('q');
        if (empty($q)) {
            $customers = Customer::selectRaw("CONCAT(firstname, ' ', lastname, ' - ', email) as title, CONCAT(phone, ' - #', id) as description, id")
                ->orderBy('created_at', 'desc')
                ->paginate($this->perPage);
        } else {
            $customers = Customer::selectRaw("CONCAT(firstname, ' ', lastname, ' - ', email) as title, CONCAT(id, ' - ', phone) as description, id")
                ->where(function ($query) use ($q) {
                    $query->orWhere('firstname', 'like', "%$q%")
                        ->orWhere('lastname', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%")
                        ->orWhere('phone', 'like', "%$q%");
                })
                ->orderBy('created_at', 'desc')
                ->paginate($this->perPage);
        }

        return response()->json($customers);
    }

    public function action(Request $request, Customer $customer, string $action)
    {
        $this->checkPermission('update', $customer);
        switch ($action) {
            case 'suspend':
                $customer->suspend($request->reason ?? 'No reason provided', $request->force ?? false, $request->notify ?? false);
                break;
            case 'reactivate':
                $customer->reactivate($request->notify ?? false);
                break;
            case 'ban':
                $customer->ban($request->reason ?? 'No reason provided', $request->force ?? false, $request->notify ?? false);
                break;
            case 'disable2FA':
                $customer->twoFactorDisable();
                break;
            case 'resetSecurityQuestion':
                $customer->resetSecurityQuestion();
                break;
            default:
                break;
        }

        return redirect()->back()->with('success', __($this->translatePrefix.'.show.action_success'));
    }

    public function addNote(Request $request, Customer $customer)
    {
        $this->checkPermission('update', $customer);
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $customer->customerNotes()->create([
            'author_id' => auth('admin')->id(),
            'content' => $validated['content'],
        ]);

        return redirect()->back()->with('success', __($this->translatePrefix.'.show.note_added'));
    }
}
