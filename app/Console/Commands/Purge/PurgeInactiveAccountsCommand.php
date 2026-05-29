<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Console\Commands\Purge;

use App\Models\Account\Customer;
use App\Models\ActionLog;
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

        $now = Carbon::now();
        $reminders = [30, 7, 1];
        $purged = 0;
        $reminded = 0;
        $scanned = 0;

        // Stream candidates in chunks to keep memory bounded on installs
        // with very large customer bases.
        Customer::query()
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
            ->orderBy('id')
            ->chunkById(100, function ($chunk) use (
                $deleter, $now, $reminders, $days, $dryRun,
                &$purged, &$reminded, &$scanned
            ) {
                foreach ($chunk as $customer) {
                    $scanned++;
                    $reference = $customer->last_login ?? $customer->created_at;
                    $eligibleAt = $reference->copy()->addDays($days);
                    $daysLeft = (int) ceil($now->floatDiffInDays($eligibleAt, false));

                    if ($daysLeft <= 0) {
                        $this->processDelete($customer, $deleter, $dryRun, $reference);
                        $purged++;
                        continue;
                    }

                    if ($this->sendMilestoneReminder($customer, $daysLeft, $reminders, $now, $dryRun)) {
                        $reminded++;
                    }
                }
            });

        $this->info(sprintf('Purged: %d, reminded: %d, candidates scanned: %d (dry-run=%s)',
            $purged, $reminded, $scanned, $dryRun ? 'yes' : 'no'));

        return self::SUCCESS;
    }

    private function processDelete(Customer $customer, AccountDeletionService $deleter, bool $dryRun, Carbon $reference): void
    {
        $this->line(sprintf('[PURGE] #%d %s (last_login=%s)',
            $customer->id, $customer->email, $reference->toDateString()));

        if ($dryRun) {
            return;
        }

        // Capture identity BEFORE delete; AccountDeletionService nulls fields.
        $payload = [
            'reason' => 'gdpr_inactive',
            'email' => $customer->email,
            'last_login' => optional($customer->last_login)->toIso8601String(),
        ];

        try {
            $deleter->delete($customer, true);
            ActionLog::log(ActionLog::ACCOUNT_DELETED, Customer::class, $customer->id, null, null, $payload);
        } catch (\Throwable $e) {
            logger()->error('gdpr.purge.delete_failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendMilestoneReminder(Customer $customer, int $daysLeft, array $reminders, Carbon $now, bool $dryRun): bool
    {
        foreach ($reminders as $milestone) {
            if ($daysLeft > $milestone || $customer->hasMetadata("gdpr_purge_reminder_{$milestone}")) {
                continue;
            }
            $this->line(sprintf('[REMIND %d] #%d %s', $milestone, $customer->id, $customer->email));
            if ($dryRun) {
                return true;
            }
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

            return true;
        }

        return false;
    }
}
