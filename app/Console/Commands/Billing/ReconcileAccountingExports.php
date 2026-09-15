<?php

namespace App\Console\Commands\Billing;

use App\Jobs\Billing\ExportAccountingDocument;
use App\Jobs\Billing\ExportAccountingPayment;
use App\Models\Billing\AccountingExport;
use App\Models\Billing\PaymentTransaction;
use App\Services\Billing\AccountingProviderRegistry;
use Illuminate\Console\Command;

class ReconcileAccountingExports extends Command
{
    protected $signature = 'accounting:reconcile {provider?}';

    protected $description = 'Réconcilie et relance les exports comptables créés après activation.';

    public function handle(AccountingProviderRegistry $registry): int
    {
        $query = AccountingExport::with('exportable')->whereIn('status', [AccountingExport::STATUS_SUBMITTED, AccountingExport::STATUS_FAILED]);
        if ($this->argument('provider')) {
            $query->where('provider', $this->argument('provider'));
        }
        $query->chunkById(100, function ($exports) use ($registry) {
            foreach ($exports as $export) {
                try {
                    if ($export->status === AccountingExport::STATUS_SUBMITTED) {
                        $result = $registry->get($export->provider)->status($export);
                        $export->update(['status' => $result->status, 'response' => $result->response, 'completed_at' => $result->status === AccountingExport::STATUS_COMPLETED ? now() : null]);
                    } elseif ($export->exportable instanceof PaymentTransaction) {
                        ExportAccountingPayment::dispatch($export->exportable, $export->provider);
                    } else {
                        ExportAccountingDocument::dispatch($export->exportable, $export->provider);
                    }
                } catch (\Throwable $exception) {
                    $export->update(['last_error_code' => class_basename($exception), 'last_error_message' => $exception->getMessage()]);
                }
            }
        });

        return self::SUCCESS;
    }
}
