<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Jobs\Billing\ExportAccountingDocument;
use App\Jobs\Billing\ExportAccountingPayment;
use App\Jobs\Billing\SubmitElectronicInvoice;
use App\Jobs\Billing\SubmitEReportingPeriod;
use App\Models\Admin\Permission;
use App\Models\Billing\AccountingExport;
use App\Models\Billing\ElectronicDocument;
use App\Models\Billing\EReportingPeriod;
use App\Models\Billing\PaymentTransaction;
use Illuminate\Support\Facades\Storage;

class ElectronicInvoicingController extends Controller
{
    public function index()
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);

        return view('admin.core.einvoicing.dashboard', [
            'documents' => ElectronicDocument::with('documentable')->whereIn('documentable_type', [\App\Models\Billing\Invoice::class, \App\Models\Billing\CreditNote::class])->latest()->paginate(25),
            'periods' => EReportingPeriod::latest('period_start')->limit(50)->get(),
            'payments' => PaymentTransaction::with('invoice')->latest('occurred_at')->limit(50)->get(),
            'accountingExports' => AccountingExport::with('exportable')->latest()->limit(100)->get(),
        ]);
    }

    public function download(string $kind, int $id)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        $path = $kind === 'period' ? EReportingPeriod::findOrFail($id)->artifact_path : ElectronicDocument::findOrFail($id)->structured_document_path;
        if ($kind === 'period' && request('file') === 'manifest' && $path) {
            $path = substr($path, 0, -4).'.manifest.json';
        }
        if ($kind === 'document' && request('file') !== 'pdf' && $path) {
            $base = str_ends_with($path, '.pdf') ? substr($path, 0, -4) : $path;
            $path = request('file') === 'manifest' ? $base.'/manifest.json' : $base.'/factur-x.xml';
        }
        abort_unless($path && str_starts_with($path, 'einvoicing/local/') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }

    public function retry(string $kind, int $id)
    {
        staff_aborts_permission(Permission::MANAGE_SETTINGS);
        if ($kind === 'accounting') {
            $export = AccountingExport::findOrFail($id);
            if ($export->exportable instanceof PaymentTransaction) {
                ExportAccountingPayment::dispatch($export->exportable, $export->provider);
            } else {
                ExportAccountingDocument::dispatch($export->exportable, $export->provider);
            }
        } elseif ($kind === 'period') {
            SubmitEReportingPeriod::dispatch(EReportingPeriod::findOrFail($id));
        } else {
            SubmitElectronicInvoice::dispatch(ElectronicDocument::findOrFail($id)->documentable);
        }

        return back()->with('success', 'Nouvelle tentative planifiée.');
    }
}
