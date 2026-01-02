<?php
/*
 * This file is part of the CLIENTXCMS project.
 * This is the Client API TicketController.
 *
 * Year: 2025
 */

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Helpdesk\ReplyTicketRequest;
use App\Http\Requests\Helpdesk\SubmitTicketRequest;

use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="Customer Tickets",
 *     description="Support ticket endpoints for customer API"
 * )
 */
class TicketController extends Controller
{
    /**
     * @OA\Get(
     *     path="/client/tickets",
     *     summary="List customer's tickets",
     *     tags={"Customer Tickets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filter by status (open, answered, customer_reply, closed)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of tickets",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::where('customer_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('filter') && in_array($request->filter, array_keys(SupportTicket::FILTERS))) {
            $query->where('status', $request->filter);
        }

        $tickets = $query->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => $tickets->map(fn($ticket) => [
                'id' => $ticket->id,
                'uuid' => $ticket->uuid,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'status_label' => __('helpdesk.support.status.' . $ticket->status),
                'priority' => $ticket->priority,
                'priority_label' => __('helpdesk.support.priority.' . $ticket->priority),
                'department' => [
                    'id' => $ticket->department->id,
                    'name' => $ticket->department->name,
                ],
                'last_reply_at' => $ticket->last_reply_at?->toIso8601String(),
                'created_at' => $ticket->created_at->toIso8601String(),
                'closed_at' => $ticket->closed_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
            'filters' => SupportTicket::FILTERS,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/client/tickets/departments",
     *     summary="List available departments",
     *     tags={"Customer Tickets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of departments"
     *     )
     * )
     */
    public function departments(Request $request): JsonResponse
    {
        $departments = SupportDepartment::all();
        $priorities = SupportTicket::getPriorities();
        $related = $request->user()->supportRelatedItems();

        return response()->json([
            'departments' => $departments->map(fn($dept) => [
                'id' => $dept->id,
                'name' => $dept->name,
                'description' => $dept->description,
            ]),
            'priorities' => $priorities,
            'related_items' => $related,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/client/tickets",
     *     summary="Create a new ticket",
     *     tags={"Customer Tickets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"department_id", "subject", "content", "priority"},
     *             @OA\Property(property="department_id", type="integer"),
     *             @OA\Property(property="subject", type="string"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}),
     *             @OA\Property(property="related_id", type="integer"),
     *             @OA\Property(property="related_type", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ticket created",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function store(SubmitTicketRequest $request): JsonResponse
    {
        $ticket = new SupportTicket();
        $ticket->fill($request->only(['department_id', 'priority', 'subject', 'related_id', 'related_type']));
        $ticket->customer_id = $request->user()->id;
        $ticket->status = SupportTicket::STATUS_OPEN;
        $ticket->save();

        $ticket->addMessage($request->content, $request->user()->id);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $attachment) {
                $ticket->addAttachment($attachment, $request->user()->id);
            }
        }

        return response()->json([
            'message' => __('helpdesk.support.ticket_created'),
            'data' => $this->formatTicket($ticket->fresh()),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/client/tickets/{ticket}",
     *     summary="Get ticket details",
     *     tags={"Customer Tickets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket details with messages",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=404, description="Ticket not found")
     * )
     */
    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        if ($ticket->customer_id !== $request->user()->id) {
            return response()->json(['error' => __('helpdesk.support.not_found')], 404);
        }

        $ticket->readMessages();

        return response()->json([
            'data' => $this->formatTicket($ticket, true),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/client/tickets/{ticket}/reply",
     *     summary="Reply to a ticket",
     *     tags={"Customer Tickets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reply added"
     *     )
     * )
     */
    public function reply(ReplyTicketRequest $request, SupportTicket $ticket): JsonResponse
    {
        if ($ticket->customer_id !== $request->user()->id) {
            return response()->json(['error' => __('helpdesk.support.not_found')], 404);
        }

        if ($ticket->isClosed()) {
            return response()->json(['error' => __('helpdesk.support.ticket_closed_reply')], 400);
        }

        $ticket->addMessage($request->content, $request->user()->id);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $attachment) {
                $ticket->addAttachment($attachment, $request->user()->id);
            }
        }

        return response()->json([
            'message' => __('helpdesk.support.ticket_replied'),
            'data' => $this->formatTicket($ticket->fresh(), true),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/client/tickets/{ticket}/close",
     *     summary="Close a ticket",
     *     tags={"Customer Tickets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket closed"
     *     )
     * )
     */
    public function close(Request $request, SupportTicket $ticket): JsonResponse
    {
        if ($ticket->customer_id !== $request->user()->id) {
            return response()->json(['error' => __('helpdesk.support.not_found')], 404);
        }

        $ticket->close('customer', $request->user()->id);

        return response()->json([
            'message' => __('helpdesk.support.ticket_closed'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/client/tickets/{ticket}/reopen",
     *     summary="Reopen a closed ticket",
     *     tags={"Customer Tickets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket reopened"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot reopen (too old)"
     *     )
     * )
     */
    public function reopen(Request $request, SupportTicket $ticket): JsonResponse
    {
        if ($ticket->customer_id !== $request->user()->id) {
            return response()->json(['error' => __('helpdesk.support.not_found')], 404);
        }

        $days = setting('support_ticket_reopen_days', 7);
        if ($ticket->closed_at && $ticket->closed_at->diffInDays(now()) > $days && $days > 0) {
            return response()->json([
                'error' => __('helpdesk.support.ticket_reopen_days', ['days' => $days]),
            ], 400);
        }

        $ticket->reopen();

        return response()->json([
            'message' => __('helpdesk.support.ticket_reopened'),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/client/tickets/{ticket}/attachments/{attachment}",
     *     summary="Download an attachment",
     *     tags={"Customer Tickets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="attachment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download"
     *     )
     * )
     */
    public function downloadAttachment(Request $request, SupportTicket $ticket, $attachment): Response
    {
        if ($ticket->customer_id !== $request->user()->id) {
            abort(404);
        }

        $attachment = $ticket->attachments()->where('id', $attachment)->first();
        if (!$attachment || $attachment->ticket_id !== $ticket->id) {
            abort(404);
        }

        return response()->download(storage_path('app/' . $attachment->path), $attachment->name);
    }

    private function formatTicket(SupportTicket $ticket, bool $withMessages = false): array
    {
        $data = [
            'id' => $ticket->id,
            'uuid' => $ticket->uuid,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'status_label' => __('helpdesk.support.status.' . $ticket->status),
            'priority' => $ticket->priority,
            'priority_label' => __('helpdesk.support.priority.' . $ticket->priority),
            'department' => [
                'id' => $ticket->department->id,
                'name' => $ticket->department->name,
            ],
            'related' => ($ticket->related_type && $ticket->related_id) ? [
                'id' => $ticket->related_id,
                'type' => $ticket->related_type,
                'name' => $ticket->related?->name ?? null,
            ] : null,
            'last_reply_at' => $ticket->last_reply_at?->toIso8601String(),
            'created_at' => $ticket->created_at->toIso8601String(),
            'closed_at' => $ticket->closed_at?->toIso8601String(),
        ];

        if ($withMessages) {
            $data['messages'] = $ticket->messages->map(fn($msg) => [
                'id' => $msg->id,
                'content' => $msg->message,
                'is_customer' => $msg->isCustomer(),
                'author' => $msg->isCustomer()
                    ? $ticket->customer->fullname
                    : ($msg->admin?->username ?? __('helpdesk.support.staff')),
                'created_at' => $msg->created_at->toIso8601String(),
                'edited_at' => $msg->edited_at?->toIso8601String(),
            ]);

            $data['attachments'] = $ticket->attachments->map(fn($att) => [
                'id' => $att->id,
                'name' => $att->name,
                'size' => $att->size,
                'download_url' => route('api.client.tickets.attachment', [$ticket->id, $att->id]),
            ]);
        }

        return $data;
    }
}
