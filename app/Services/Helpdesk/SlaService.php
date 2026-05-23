<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Services\Helpdesk;

use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportMessage;
use App\Models\Helpdesk\SupportTicket;
use Illuminate\Support\Carbon;

/**
 * v2.16 — Helpdesk SLA tracking.
 *
 * Departments declare two intervals in minutes:
 *   - sla_first_response_minutes  → due_at for the *first staff reply*
 *   - sla_resolution_minutes      → due_at for the *closure*
 *
 * When a ticket is opened the service computes the absolute timestamps
 * and stores them on the row. When staff replies for the first time,
 * `first_response_at` is set, stopping the "first response" clock.
 * Closing the ticket is the only thing that closes the "resolution"
 * clock — SupportTicket::close() already records `closed_at` and we
 * read that.
 */
class SlaService
{
    public function applyOnCreate(SupportTicket $ticket): void
    {
        $department = $ticket->department;
        if ($department === null) {
            return;
        }

        $base = $ticket->created_at ?? Carbon::now();
        $dirty = false;

        $firstResponseMinutes = $department->getAttribute('sla_first_response_minutes');
        if ($firstResponseMinutes !== null && $ticket->first_response_due_at === null) {
            $ticket->first_response_due_at = $base->copy()->addMinutes((int) $firstResponseMinutes);
            $dirty = true;
        }

        $resolutionMinutes = $department->getAttribute('sla_resolution_minutes');
        if ($resolutionMinutes !== null && $ticket->resolution_due_at === null) {
            $ticket->resolution_due_at = $base->copy()->addMinutes((int) $resolutionMinutes);
            $dirty = true;
        }

        if ($dirty) {
            $ticket->save();
        }
    }

    /**
     * Mark the first staff response time on the ticket if it has not
     * been recorded yet. Called from the reply controllers right after
     * the staff message has been persisted.
     */
    public function recordFirstResponse(SupportTicket $ticket, SupportMessage $message): void
    {
        if (! $message->isStaff()) {
            return;
        }
        if ($ticket->first_response_at !== null) {
            return;
        }
        $ticket->first_response_at = $message->created_at ?? Carbon::now();
        $ticket->save();
    }

    /**
     * Compute the breach state for monitoring / dashboards.
     *
     * @return array{first_response_breached: bool, resolution_breached: bool,
     *               first_response_due_in: ?int, resolution_due_in: ?int}
     */
    public function statusFor(SupportTicket $ticket): array
    {
        $now = Carbon::now();
        $firstBreached = $ticket->first_response_at === null
            && $ticket->first_response_due_at !== null
            && $now->greaterThan($ticket->first_response_due_at);

        $resolutionBreached = $ticket->isOpen() === false
            ? false
            : ($ticket->resolution_due_at !== null && $now->greaterThan($ticket->resolution_due_at));

        return [
            'first_response_breached' => $firstBreached,
            'resolution_breached' => $resolutionBreached,
            'first_response_due_in' => $ticket->first_response_due_at?->diffInMinutes($now, false),
            'resolution_due_in' => $ticket->resolution_due_at?->diffInMinutes($now, false),
        ];
    }

    /**
     * Returns tickets that recently breached an SLA and have not yet
     * been flagged. Used by the helpdesk:notify-sla-breach command.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, SupportTicket>
     */
    public function freshBreaches()
    {
        $now = Carbon::now();
        return SupportTicket::query()
            ->whereNull('sla_breached_notified_at')
            ->where('status', SupportTicket::STATUS_OPEN)
            ->where(function ($q) use ($now) {
                $q->where(function ($q2) use ($now) {
                    $q2->whereNull('first_response_at')
                        ->whereNotNull('first_response_due_at')
                        ->where('first_response_due_at', '<=', $now);
                })->orWhere(function ($q2) use ($now) {
                    $q2->whereNotNull('resolution_due_at')
                        ->where('resolution_due_at', '<=', $now);
                });
            })
            ->get();
    }
}
