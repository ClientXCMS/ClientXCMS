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

namespace App\Services\Helpdesk;

use App\Models\Helpdesk\SupportMessage;
use App\Models\Helpdesk\SupportTicket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class TicketStatisticsService
{
    public function getClosedTicketStats()
    {
        $closedTickets = SupportTicket::where('status', SupportTicket::STATUS_CLOSED)
            ->whereNotNull('closed_at')
            ->select('id', 'created_at', 'first_response_at', 'closed_at')
            ->get();

        $totalReplySeconds = 0;
        $totalResolutionSeconds = 0;
        $ticketsWithAdminReply = 0;
        $closedTicketCount = $closedTickets->count();

        foreach ($closedTickets as $ticket) {
            if ($ticket->closed_at && $ticket->created_at) {
                $totalResolutionSeconds += $ticket->closed_at->diffInSeconds($ticket->created_at);
            }
            if ($ticket->first_response_at && $ticket->created_at) {
                $totalReplySeconds += $ticket->first_response_at->diffInSeconds($ticket->created_at);
                $ticketsWithAdminReply++;
            }
        }

        $avgReplySeconds = $ticketsWithAdminReply > 0 ? $totalReplySeconds / $ticketsWithAdminReply : 0;
        $avgResolutionSeconds = $closedTicketCount > 0 ? $totalResolutionSeconds / $closedTicketCount : 0;

        return [
            'avg_reply_time' => $avgReplySeconds > 0 ? Carbon::now()->subSeconds(round($avgReplySeconds))->diffForHumans(null, true, false, 2) : __('N/A'),
            'avg_resolution_time' => $avgResolutionSeconds > 0 ? Carbon::now()->subSeconds(round($avgResolutionSeconds))->diffForHumans(null, true, false, 2) : __('N/A'),
        ];
    }

    public function getTicketsToReply()
    {
        return QueryBuilder::for(SupportTicket::class)
            ->where('status', SupportTicket::STATUS_ANSWERED)
            ->with('customer:id,firstname,lastname', 'department:id,name')
            ->orderBy('updated_at', 'asc')
            ->allowedFilters(['department_id', 'priority', 'id', 'customer.email', 'subject', 'uuid'])
            ->allowedSorts(['updated_at'])
            ->get()
            ->filter(fn($ticket) => $ticket->staffCanView(auth('admin')->user()));
    }

    public function getActiveTickets()
    {
        return QueryBuilder::for(SupportTicket::class)
            ->where('status', SupportTicket::STATUS_OPEN)
            ->with('customer:id,firstname,lastname', 'department:id,name')
            ->orderBy('updated_at', 'asc')
            ->allowedFilters(['department_id', 'priority', 'id', 'customer.email', 'uuid'])
            ->allowedSorts(['updated_at'])
            ->get()
            ->filter(fn($ticket) => $ticket->staffCanView(auth('admin')->user()));
    }

    public function getPriorityTickets()
    {
        $now = now();
        return QueryBuilder::for(SupportTicket::class)
            ->where('status', SupportTicket::STATUS_OPEN)
            ->with('customer:id,firstname,lastname', 'department:id,name')
            ->get()
            ->filter(fn($ticket) => $ticket->staffCanView(auth('admin')->user()))
            ->map(function ($ticket) use ($now) {
                $slaBreached = false;
                $slaDueSeconds = null;
                $priorityWeight = 0;

                // Priority mapping
                if ($ticket->priority === 'high') {
                    $priorityWeight = 30;
                } elseif ($ticket->priority === 'medium') {
                    $priorityWeight = 20;
                } else {
                    $priorityWeight = 10;
                }

                if ($ticket->first_response_at === null && $ticket->first_response_due_at !== null) {
                    $slaDueSeconds = $now->diffInSeconds($ticket->first_response_due_at, false);
                    if ($slaDueSeconds < 0) {
                        $slaBreached = true;
                        $priorityWeight = 1000 + abs($slaDueSeconds);
                    } else {
                        // Due soon (within 24 hours gets boosted)
                        if ($slaDueSeconds < 86400) {
                            $priorityWeight = 500 + ((86400 - $slaDueSeconds) / 100);
                        } else {
                            $priorityWeight = 100;
                        }
                    }
                } elseif ($ticket->resolution_due_at !== null) {
                    $slaDueSeconds = $now->diffInSeconds($ticket->resolution_due_at, false);
                    if ($slaDueSeconds < 0) {
                        $slaBreached = true;
                        $priorityWeight = 800 + abs($slaDueSeconds);
                    } else {
                        if ($slaDueSeconds < 86400) {
                            $priorityWeight = 400 + ((86400 - $slaDueSeconds) / 100);
                        } else {
                            $priorityWeight = 80;
                        }
                    }
                }

                $ticket->sla_breached = $slaBreached;
                $ticket->sla_due_seconds = $slaDueSeconds;
                $ticket->priority_weight = $priorityWeight;

                return $ticket;
            })
            ->filter(function ($ticket) {
                return $ticket->priority === 'high' || $ticket->sla_due_seconds !== null;
            })
            ->sortByDesc('priority_weight');
    }

    public function getStaffMessageCounts()
    {
        return SupportMessage::whereNotNull('admin_id')
            ->select('admin_id', DB::raw('count(*) as message_count'))
            ->groupBy('admin_id')
            ->with('admin:id,username,firstname,lastname')
            ->orderBy('message_count', 'desc')
            ->limit(10)
            ->get();
    }

    public function getDepartmentTicketCounts()
    {
        return SupportTicket::select('department_id', DB::raw('count(*) as ticket_count'))
            ->groupBy('department_id')
            ->with('department:id,name,icon')
            ->orderBy('ticket_count', 'desc')
            ->limit(10)
            ->get();
    }

    public function getWeeklyGraphLabels()
    {
        $labels = [];
        for ($i = 0; $i < 52; $i++) {
            $date = now()->subWeeks($i);
            $labels[] = $date->startOfWeek()->format('d/m') . ' - ' .
                $date->endOfWeek()->format('d/m');
        }

        return json_encode([$labels]);
    }

    public function getWeeklyGraphData()
    {
        $startDate = now()->subWeeks(51)->startOfWeek();
        $endDate = now()->endOfWeek();

        $tickets = SupportTicket::query()
            ->select(DB::raw('count(id) as aggregate'), DB::raw('DATE(created_at) as date'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->date)->startOfWeek()->format('Y-m-d'))
            ->map(fn($group) => $group->sum('aggregate'));

        $messagesData = SupportMessage::query()
            ->select(DB::raw('count(id) as aggregate'), DB::raw('DATE(created_at) as date'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->date)->startOfWeek()->format('Y-m-d'))
            ->map(fn($group) => $group->sum('aggregate'));

        $data = [];
        $messages = [];
        for ($i = 0; $i < 52; $i++) {
            $date = now()->subWeeks($i);
            $key = $date->startOfWeek()->format('Y-m-d');
            $data[] = $tickets->get($key, 0);
            $messages[] = $messagesData->get($key, 0);
        }

        return json_encode([$data, $messages]);
    }

    public function getSlaStats(): array
    {
        $now = Carbon::now();

        // 1. Open/active tickets that have already breached SLA
        $openBreachedCount = SupportTicket::where('status', '!=', SupportTicket::STATUS_CLOSED)
            ->where(function ($q) use ($now) {
                $q->where(function ($q2) use ($now) {
                    $q2->whereNull('first_response_at')
                        ->whereNotNull('first_response_due_at')
                        ->where('first_response_due_at', '<', $now);
                })->orWhere(function ($q2) use ($now) {
                    $q2->whereNotNull('resolution_due_at')
                        ->where('resolution_due_at', '<', $now);
                });
            })->count();

        // 2. Closed tickets with SLA targets, checking if they were met or breached
        $closedTickets = SupportTicket::where('status', SupportTicket::STATUS_CLOSED)
            ->where(function ($q) {
                $q->whereNotNull('first_response_due_at')
                    ->orWhereNotNull('resolution_due_at');
            })->get();

        $closedTotal = $closedTickets->count();
        $closedBreached = 0;

        foreach ($closedTickets as $ticket) {
            $breached = false;
            if ($ticket->first_response_due_at !== null) {
                $respAt = $ticket->first_response_at ?? $ticket->closed_at;
                if ($respAt !== null && $respAt->greaterThan($ticket->first_response_due_at)) {
                    $breached = true;
                }
            }
            if ($ticket->resolution_due_at !== null) {
                if ($ticket->closed_at !== null && $ticket->closed_at->greaterThan($ticket->resolution_due_at)) {
                    $breached = true;
                }
            }
            if ($breached) {
                $closedBreached++;
            }
        }

        $complianceRate = $closedTotal > 0 ? round((($closedTotal - $closedBreached) / $closedTotal) * 100) : 100;

        return [
            'open_breached_count' => $openBreachedCount,
            'compliance_rate' => $complianceRate,
        ];
    }
}
