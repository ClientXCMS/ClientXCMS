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
use App\Models\ActionLog;
use App\Models\Billing\CreditNote;
use App\Models\Billing\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditNoteController extends Controller
{
    public function index(Request $request)
    {
        staff_aborts_permission('admin.show_invoices');

        $currencies = CreditNote::query()
            ->select('currency')
            ->whereNotNull('currency')
            ->distinct()
            ->orderBy('currency')
            ->pluck('currency');

        $query = CreditNote::query()
            ->with(['customer', 'invoice', 'admin'])
            ->latest();

        $this->applyIndexFilters($query, $request);

        $summaryQuery = (clone $query)->reorder()->setEagerLoads([]);
        $summaryRows = $summaryQuery
            ->select([
                'currency',
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(amount), 0) as amount'),
                DB::raw('COALESCE(SUM(tax), 0) as tax'),
                DB::raw('COALESCE(SUM(amount + tax), 0) as total'),
            ])
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        $items = $query->paginate(25)->appends($request->query());
        $filterValues = $request->query('filter', []);
        $filterValues = is_array($filterValues) ? $filterValues : [];
        $searchDefinitions = $this->getSearchFields();
        $searchDefinitions['currency_filter'] = [
            'label' => __('global.currency'),
            'type' => 'select',
            'fields' => ['currency'],
            'options' => $currencies->mapWithKeys(fn ($currency) => [$currency => $currency])->all(),
        ];
        $selectedSearchField = ! blank($filterValues['currency'] ?? null)
            ? 'currency_filter'
            : $this->getSearchField($request);

        return view('admin.core.credit_notes.index', [
            'items' => $items,
            'summaryRows' => $summaryRows,
            'summaryTotals' => [
                'count' => (int) $summaryRows->sum('count'),
                'amount' => (float) $summaryRows->sum('amount'),
                'tax' => (float) $summaryRows->sum('tax'),
                'total' => (float) $summaryRows->sum('total'),
            ],
            'dateFrom' => $request->input('filter.date_from', $request->query('date_from')),
            'dateTo' => $request->input('filter.date_to', $request->query('date_to')),
            'filters' => $this->getIndexFilters($currencies),
            'checkedFilters' => $this->getCheckedFilters($request, $currencies->all()),
            'searchFields' => $this->getSearchFields(),
            'searchDefinitions' => $searchDefinitions,
            'searchValues' => [
                'credit_note_number' => $filterValues['credit_note_number'] ?? null,
                'customer.email' => $filterValues['customer.email'] ?? null,
                'invoice.invoice_number' => $filterValues['invoice.invoice_number'] ?? null,
                'date_from' => $request->input('filter.date_from', $request->query('date_from')),
                'date_to' => $request->input('filter.date_to', $request->query('date_to')),
                'currency' => $filterValues['currency'] ?? null,
            ],
            'search' => $this->getSearchValue($request),
            'searchField' => $selectedSearchField,
            'filterField' => 'currency',
        ]);
    }

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
                'remaining' => formatted_price($remainingAmount, $invoice->currency),
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
                ['model' => 'Credit Note #'.$creditNote->credit_note_number]
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
                ['model' => 'Credit Note #'.$number]
            );

            $creditNote->delete();
        });

        return back()->with('success', __('admin.credit_notes.deleted_success', ['number' => $number]));
    }

    private function applyIndexFilters(Builder $query, Request $request): void
    {
        $dateFrom = $this->parseDateFilter($request->input('filter.date_from', $request->query('date_from')));
        if ($dateFrom !== null) {
            $query->where('created_at', '>=', $dateFrom->startOfDay());
        }

        $dateTo = $this->parseDateFilter($request->input('filter.date_to', $request->query('date_to')));
        if ($dateTo !== null) {
            $query->where('created_at', '<=', $dateTo->endOfDay());
        }

        $filters = $request->query('filter', []);
        if (! is_array($filters)) {
            $filters = [];
        }

        $currencies = $this->splitFilterValues($filters['currency'] ?? null);
        if (! empty($currencies) && ! in_array('all', $currencies, true)) {
            $query->whereIn('currency', $currencies);
        }

        if (! blank($filters['credit_note_number'] ?? null)) {
            $query->where('credit_note_number', 'like', '%'.trim((string) $filters['credit_note_number']).'%');
        }

        if (! blank($filters['customer.email'] ?? null)) {
            $customerSearch = trim((string) $filters['customer.email']);
            $query->whereHas('customer', function ($query) use ($customerSearch) {
                $query->where('email', 'like', '%'.$customerSearch.'%')
                    ->orWhere('firstname', 'like', '%'.$customerSearch.'%')
                    ->orWhere('lastname', 'like', '%'.$customerSearch.'%');
            });
        }

        if (! blank($filters['invoice.invoice_number'] ?? null)) {
            $invoiceSearch = trim((string) $filters['invoice.invoice_number']);
            $query->whereHas('invoice', function ($query) use ($invoiceSearch) {
                $query->where('invoice_number', 'like', '%'.$invoiceSearch.'%');
            });
        }

        $search = trim((string) $request->query('q', ''));
        if ($search === '') {
            return;
        }

        $query->where(function ($query) use ($search) {
            $query->where('credit_note_number', 'like', '%'.$search.'%')
                ->orWhereHas('customer', function ($query) use ($search) {
                    $query->where('email', 'like', '%'.$search.'%')
                        ->orWhere('firstname', 'like', '%'.$search.'%')
                        ->orWhere('lastname', 'like', '%'.$search.'%');
                })
                ->orWhereHas('invoice', function ($query) use ($search) {
                    $query->where('invoice_number', 'like', '%'.$search.'%');
                });
        });
    }

    private function getIndexFilters($currencies): array
    {
        $filters = ['all' => __('global.states.all')];

        foreach ($currencies as $currency) {
            $filters[$currency] = $currency;
        }

        return $filters;
    }

    private function getSearchFields(): array
    {
        return [
            'credit_note_number' => ['label' => __('admin.credit_notes.credit_note_number'), 'type' => 'text', 'fields' => ['credit_note_number'], 'options' => []],
            'customer.email' => ['label' => __('global.customer'), 'type' => 'text', 'fields' => ['customer.email'], 'options' => []],
            'invoice.invoice_number' => ['label' => __('admin.credit_notes.original_invoice'), 'type' => 'text', 'fields' => ['invoice.invoice_number'], 'options' => []],
            'created_at_range' => [
                'label' => __('global.created'),
                'type' => 'date_range',
                'fields' => ['date_from', 'date_to'],
                'options' => [],
            ],
        ];
    }

    private function getCheckedFilters(Request $request, array $currencies): array
    {
        $filters = $request->query('filter', []);
        if (! is_array($filters)) {
            $filters = [];
        }

        $checkedFilters = [];
        foreach ($this->splitFilterValues($filters['currency'] ?? null) as $currency) {
            if (in_array($currency, $currencies, true)) {
                $checkedFilters[] = $currency;
            }
        }

        return $checkedFilters;
    }

    private function getSearchValue(Request $request): ?string
    {
        $filters = $request->query('filter', []);
        if (! is_array($filters)) {
            return $this->stringQuery($request, 'q');
        }

        foreach (array_keys($this->getSearchFields()) as $field) {
            if (! blank($filters[$field] ?? null)) {
                return (string) $filters[$field];
            }
        }

        return $this->stringQuery($request, 'q');
    }

    private function getSearchField(Request $request): string
    {
        $filters = $request->query('filter', []);
        if (is_array($filters)) {
            if (! blank($filters['date_from'] ?? null) || ! blank($filters['date_to'] ?? null)) {
                return 'created_at_range';
            }
            foreach (array_keys($this->getSearchFields()) as $field) {
                if (! blank($filters[$field] ?? null)) {
                    return $field;
                }
            }
        }

        return 'credit_note_number';
    }

    private function splitFilterValues(mixed $value): array
    {
        if (blank($value)) {
            return [];
        }

        if (is_array($value)) {
            return array_filter($value, fn ($item) => ! blank($item));
        }

        return array_filter(explode(',', (string) $value), fn ($item) => ! blank($item));
    }

    private function stringQuery(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_scalar($value) && ! blank($value) ? (string) $value : null;
    }

    private function parseDateFilter(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
