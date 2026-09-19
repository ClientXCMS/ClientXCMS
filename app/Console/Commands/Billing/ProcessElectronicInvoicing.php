<?php

namespace App\Console\Commands\Billing;

use App\Jobs\Billing\SubmitEReportingPeriod;
use App\Models\Billing\EReportingPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ProcessElectronicInvoicing extends Command
{
    protected $signature = 'einvoicing:process {--dry-run} {--date=} {--type=} {--period=}';

    protected $description = 'Ferme et transmet les périodes de facturation électronique arrivées à échéance.';

    public function handle(): int
    {
        $reference = CarbonImmutable::parse($this->option('date') ?: 'now', setting('einvoicing_timezone', 'Europe/Paris'));
        $query = EReportingPeriod::query()->whereDate('period_end', '<', $reference->toDateString());
        if ($this->option('type')) {
            $query->where('type', $this->option('type'));
        }
        if ($this->option('period')) {
            $query->where('id', $this->option('period'));
        }
        $periods = $query->whereIn('status', ['open', 'closed', 'failed'])->get();
        foreach ($periods as $period) {
            $this->line(sprintf('#%d %s %s → %s (échéance %s)', $period->id, $period->type, $period->period_start->toDateString(), $period->period_end->toDateString(), $period->due_at->toIso8601String()));
            if (! $this->option('dry-run')) {
                if ($period->status === 'open') {
                    $period->update(['status' => 'closed']);
                }
                if ($period->due_at->lte($reference)) {
                    SubmitEReportingPeriod::dispatch($period);
                }
            }
        }
        $this->info($this->option('dry-run') ? 'Simulation terminée.' : 'Traitement planifié.');

        return self::SUCCESS;
    }
}
