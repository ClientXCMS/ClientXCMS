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

use App\Models\Helpdesk\SupportTicket;
use App\Models\Helpdesk\SupportMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class TicketStatisticsService
{
    public function getClosedTicketStats()
    {
        $closedTickets = SupportTicket::where('status', SupportTicket::STATUS_CLOSED)
            ->whereNotNull('closed_at')
            ->with(['messages' => fn($q) => $q->orderBy('created_at', 'asc')])
            ->get();

        $totalReplySeconds = 0;
        $totalResolutionSeconds = 0;
        $ticketsWithAdminReply = 0;
        $closedTicketCount = $closedTickets->count();

        foreach ($closedTickets as $ticket) {
            $totalResolutionSeconds += $ticket->closed_at->diffInSeconds($ticket->created_at);
            $firstAdminMessage = $ticket->messages->firstWhere('admin_id', '!=', null);
            if ($firstAdminMessage) {
                $totalReplySeconds += $firstAdminMessage->created_at->diffInSeconds($ticket->created_at);
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
            ->allowedFilters(['department_id', 'priority'])
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
            ->allowedFilters(['department_id', 'priority'])
            ->allowedSorts(['updated_at'])
            ->get()
            ->filter(fn($ticket) => $ticket->staffCanView(auth('admin')->user()));
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
            $labels[] = $date->startOfWeek()->format('d/m').' - '.
                $date->endOfWeek()->format('d/m');
        }

        return json_encode([$labels]);
    }

    public function getWeeklyGraphData()
    {
        $data = [];
        $messages = [];
        for ($i = 0; $i < 52; $i++) {
            $date = now()->subWeeks($i);
            $data[] = SupportTicket::whereBetween('created_at', [$date->startOfWeek()->toDate(), $date->endOfWeek()->toDate()])->count();
            $messages[] = SupportMessage::whereBetween('created_at', [$date->startOfWeek()->toDate(), $date->endOfWeek()->toDate()])->count();
        }

        return json_encode([$data, $messages]);
    }
}
