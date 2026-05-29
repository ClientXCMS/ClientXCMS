<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Console\Commands\Purge;

use App\Models\Account\Customer;
use App\Notifications\Account\AccountPurgeReminder;
use App\Services\Account\AccountDeletionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * v2.16 — GDPR Article-5 (storage limitation) helper.
 *
 * Scans customers that:
 *   - have never paid an invoice (no `paid_at` on any of theirs)
 *   - have no active or pending service
 *   - have not logged in for `gdpr_purge_inactive_days` days
 *
 * For each match the command:
 *   1. sends a "your account is about to be deleted" reminder at D-30,
 *      D-7, D-1 (idempotent via metadata so we never spam).
 *   2. on D-0 deletes the account through {@see AccountDeletionService}
 *      (soft delete + dissociation of all references).
 *
 * Operators opt in by setting `gdpr_purge_inactive_days` to a positive
 * integer (default: 0 = disabled). Dry-run via --dry-run.
 *
 * Schedule it daily via app/Console/Kernel:
 *   $schedule->command('purge:inactive-accounts')->dailyAt('03:00');
 */
class PurgeInactiveAccountsCommand extends Command
{
    protected $signature = 'purge:inactive-accounts {--dry-run}';

    protected $description = 'Delete GDPR-eligible inactive customer accounts (storage limitation)';

    public function handle(AccountDeletionService $deleter): int
    {
        $days = (int) setting('gdpr_purge_inactive_days', 0);
        if ($days <= 0) {
            $this->info('gdpr_purge_inactive_days is disabled — nothing to do.');
            return self::SUCCESS;
        }

        $skipWithInvoices = (bool) setting('gdpr_purge_skip_with_invoice', true);
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = Carbon::now()->subDays($days);

        $candidates = Customer::query()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_login')
                    ->orWhere('last_login', '<', $cutoff);
            })
            ->where('created_at', '<', $cutoff)
            ->when($skipWithInvoices, function ($q) {
                $q->whereDoesntHave('invoices', function ($i) {
                    $i->whereNotNull('paid_at');
                });
            })
            ->whereDoesntHave('services', function ($s) {
                $s->whereIn('status', [
                    \App\Models\Provisioning\Service::STATUS_ACTIVE,
                    \App\Models\Provisioning\Service::STATUS_PENDING,
                ]);
            })
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No inactive accounts to purge.');
            return self::SUCCESS;
        }

        $now = Carbon::now();
        $reminders = [30, 7, 1];
        $purged = 0;
        $reminded = 0;

        foreach ($candidates as $customer) {
            $reference = $customer->last_login ?? $customer->created_at;
            $eligibleAt = $reference->copy()->addDays($days);
            $daysLeft = (int) ceil($now->floatDiffInDays($eligibleAt, false));

            // D-0 — actually delete.
            if ($daysLeft <= 0) {
                $this->line(sprintf('[PURGE] #%d %s (last_login=%s)',
                    $customer->id, $customer->email, $reference->toDateString()));

                if (! $dryRun) {
                    $deleter->delete($customer, true);
                }
                $purged++;
                continue;
            }

            // Otherwise send a reminder when crossing one of the
            // configured milestones. The metadata "gdpr_purge_reminder_{N}"
            // ensures we don't notify the same customer twice for the
            // same milestone.
            foreach ($reminders as $milestone) {
                if ($daysLeft <= $milestone && ! $customer->hasMetadata("gdpr_purge_reminder_{$milestone}")) {
                    $this->line(sprintf('[REMIND %d] #%d %s', $milestone, $customer->id, $customer->email));
                    if (! $dryRun) {
                        try {
                            $customer->notify(new AccountPurgeReminder($daysLeft));
                            $customer->attachMetadata("gdpr_purge_reminder_{$milestone}", $now->toIso8601String());
                        } catch (\Throwable $e) {
                            logger()->warning('gdpr.purge.reminder_failed', [
                                'customer_id' => $customer->id,
                                'days_left' => $daysLeft,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    $reminded++;
                    break;
                }
            }
        }

        $this->info(sprintf('Purged: %d, reminded: %d, candidates scanned: %d (dry-run=%s)',
            $purged, $reminded, $candidates->count(), $dryRun ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}
