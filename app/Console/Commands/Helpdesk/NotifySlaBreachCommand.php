<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Console\Commands\Helpdesk;

use App\Models\Helpdesk\SupportTicket;
use App\Services\Helpdesk\SlaService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * v2.16 — flags newly-breached SLA tickets so staff can react. Each
 * matching ticket gets `sla_breached_notified_at = now()` so we don't
 * fire a notification more than once.
 *
 * The actual notification channel is intentionally pluggable: by
 * default we just emit a structured log line that monitoring stacks
 * pick up. An extension or installation hook can listen for the
 * model event and wire Slack / Discord / mail.
 *
 * Schedule it every 5 minutes in app/Console/Kernel.
 */
class NotifySlaBreachCommand extends Command
{
    protected $signature = 'helpdesk:notify-sla-breach
                            {--dry-run : list matches without flagging them}';

    protected $description = 'Flag helpdesk tickets that have just breached their SLA';

    public function handle(SlaService $sla): int
    {
        $breaches = $sla->freshBreaches();

        if ($breaches->isEmpty()) {
            $this->info('No fresh SLA breach detected.');
            return self::SUCCESS;
        }

        foreach ($breaches as $ticket) {
            /** @var SupportTicket $ticket */
            $this->line(sprintf(
                '[SLA] #%d "%s" — first_response_at=%s, due=%s, resolution_due=%s',
                $ticket->id,
                $ticket->subject,
                $ticket->first_response_at?->toIso8601String() ?? '-',
                $ticket->first_response_due_at?->toIso8601String() ?? '-',
                $ticket->resolution_due_at?->toIso8601String() ?? '-',
            ));

            logger()->warning('helpdesk.sla.breach', [
                'ticket_id' => $ticket->id,
                'department_id' => $ticket->department_id,
                'priority' => $ticket->priority,
                'first_response_at' => $ticket->first_response_at,
                'first_response_due_at' => $ticket->first_response_due_at,
                'resolution_due_at' => $ticket->resolution_due_at,
            ]);

            if (! $this->option('dry-run')) {
                $ticket->sla_breached_notified_at = Carbon::now();
                $ticket->save();
            }
        }

        $this->info(sprintf('Flagged %d ticket(s).', $breaches->count()));
        return self::SUCCESS;
    }
}
