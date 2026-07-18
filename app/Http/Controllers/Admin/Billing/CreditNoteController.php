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

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\Account\Customer;
use App\Models\ActionLog;
use App\Models\Billing\CreditNote;
use App\Models\Billing\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class CreditNoteController extends AbstractCrudController
{
    protected string $viewPath = 'admin.core.credit_notes';

    protected string $routePath = 'admin.credit_notes';

    protected string $translatePrefix = 'admin.credit_notes';

    protected string $model = CreditNote::class;

    protected string $filterField = 'currency';

    protected array $relations = ['customer', 'invoice', 'admin'];

    private $summaryRows;

    protected function queryIndex(): LengthAwarePaginator
    {
        $query = QueryBuilder::for($this->model)
            ->allowedFilters($this->getAllowedSearchFilters())
            ->with($this->relations)
            ->orderByDesc('created_at');

        $this->summaryRows = (clone $query)->getEloquentBuilder()
            ->reorder()
            ->setEagerLoads([])
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

        return $query->paginate($this->perPage)->appends(request()->query());
    }

    protected function getIndexParams($items, string $translatePrefix)
    {
        $params = parent::getIndexParams($items, $translatePrefix);
        $summaryRows = $this->summaryRows ?? collect();

        return array_merge($params, [
            'summaryRows' => $summaryRows,
            'summaryTotals' => [
                'count' => (int) $summaryRows->sum('count'),
                'amount' => (float) $summaryRows->sum('amount'),
                'tax' => (float) $summaryRows->sum('tax'),
                'total' => (float) $summaryRows->sum('total'),
            ],
            'dateFrom' => request()->input('filter.date_from'),
            'dateTo' => request()->input('filter.date_to'),
        ]);
    }

    protected function getIndexFilters(): array
    {
        return ['all' => __('global.states.all')] + $this->currencies();
    }

    protected function getSearchFields(): array
    {
        return [
            'credit_note_number' => __('client.invoices.credit_note_number'),
            'customer.email' => __('global.customer'),
            'invoice.invoice_number' => __('admin.credit_notes.original_invoice'),
            'currency_filter' => [
                'label' => __('global.currency'),
                'type' => 'select',
                'fields' => ['currency'],
                'options' => $this->currencies(),
            ],
        ];
    }

    protected function getPermissions(string $tablename): array
    {
        return [
            'showAny' => 'admin.show_invoices',
            'show' => 'admin.show_invoices',
            'create' => 'admin.manage_invoices',
            'update' => 'admin.manage_invoices',
            'delete' => 'admin.manage_invoices',
        ];
    }

    public function store(Request $request, Customer $customer)
    {
        $this->checkPermission('create');

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
        $this->checkPermission('show', $creditNote);

        return $creditNote->pdf();
    }

    public function download(CreditNote $creditNote)
    {
        $this->checkPermission('show', $creditNote);

        return $creditNote->download();
    }

    public function destroy(CreditNote $creditNote)
    {
        $this->checkPermission('delete', $creditNote);

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

    private function currencies(): array
    {
        return CreditNote::query()
            ->whereNotNull('currency')
            ->distinct()
            ->orderBy('currency')
            ->pluck('currency', 'currency')
            ->all();
    }
}
