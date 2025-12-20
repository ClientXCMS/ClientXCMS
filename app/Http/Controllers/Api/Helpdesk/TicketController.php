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
namespace App\Http\Controllers\Api\Helpdesk;

use App\Http\Controllers\Api\AbstractApiController;
use App\Http\Requests\Helpdesk\ReplyTicketRequest;
use App\Http\Requests\Helpdesk\SubmitTicketRequest;
use App\Http\Requests\Helpdesk\UpdateTicketRequest;
use App\Models\Helpdesk\SupportTicket;
use Illuminate\Http\Request;

class TicketController extends AbstractApiController
{
    protected string $model = SupportTicket::class;

    protected array $sorts = [
        'id',
        'subject',
        'status',
        'priority',
        'department_id',
        'customer_id',
        'assigned_to',
        'created_at',
        'updated_at',
        'closed_at',
    ];

    protected array $relations = [
        'customer',
        'department',
        'messages',
        'attachments',
        'assignedTo',
        'comments',
    ];

    protected array $filters = [
        'id',
        'subject',
        'status',
        'priority',
        'department_id',
        'customer_id',
        'assigned_to',
        'created_at',
        'updated_at',
    ];

    /**
     * @OA\Get(
     *     path="/application/tickets",
     *     summary="Get a list of support tickets",
     *     tags={"Tickets"},
     *     @OA\Response(
     *         response=200,
     *         description="A list of support tickets",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/SupportTicket"))
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string", default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="filter[status]",
     *         in="query",
     *         description="Filter by status (open, closed, answered)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter[priority]",
     *         in="query",
     *         description="Filter by priority (low, medium, high)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter[department_id]",
     *         in="query",
     *         description="Filter by department ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="filter[customer_id]",
     *         in="query",
     *         description="Filter by customer ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Related resources to include",
     *         required=false,
     *         @OA\Schema(type="string", default="customer,department,messages")
     *     )
     * )
     */
    public function index(Request $request)
    {
        return $this->queryIndex($request);
    }

    /**
     * @OA\Post(
     *     path="/application/tickets",
     *     summary="Create a new support ticket",
     *     tags={"Tickets"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"department_id", "customer_id", "subject", "priority", "content"},
     *             @OA\Property(property="department_id", type="integer", example=1),
     *             @OA\Property(property="customer_id", type="integer", example=1),
     *             @OA\Property(property="subject", type="string", example="Issue with my service"),
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="medium"),
     *             @OA\Property(property="content", type="string", example="I'm having issues with my service..."),
     *             @OA\Property(property="related_type", type="string", nullable=true, enum={"service", "invoice"}),
     *             @OA\Property(property="related_id", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ticket created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SupportTicket")
     *     )
     * )
     */
    public function store(SubmitTicketRequest $request)
    {
        $validated = $request->validated();
        $validated['customer_id'] = $request->input('customer_id');
        $ticket = SupportTicket::create($validated);
        $ticket->addMessage($validated['content'], null, auth('admin')->id());
        
        return response()->json($ticket->load(['customer', 'department', 'messages']), 201);
    }

    /**
     * @OA\Get(
     *     path="/application/tickets/{ticket}",
     *     summary="Get a single support ticket",
     *     tags={"Tickets"},
     *     @OA\Response(
     *         response=200,
     *         description="A single support ticket",
     *         @OA\JsonContent(ref="#/components/schemas/SupportTicket")
     *     ),
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         description="ID or UUID of the ticket",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function show(int $ticket)
    {
        return $this->queryShow($ticket);
    }

    /**
     * @OA\Put(
     *     path="/application/tickets/{ticket}",
     *     summary="Update an existing support ticket",
     *     tags={"Tickets"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"department_id", "subject", "priority"},
     *             @OA\Property(property="department_id", type="integer", example=1),
     *             @OA\Property(property="subject", type="string", example="Updated subject"),
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="high"),
     *             @OA\Property(property="assigned_to", type="integer", nullable=true),
     *             @OA\Property(property="close_reason", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SupportTicket")
     *     ),
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         description="ID or UUID of the ticket",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function update(UpdateTicketRequest $request, SupportTicket $ticket)
    {
        $ticket->update($request->validated());
        return response()->json($ticket->load(['customer', 'department', 'messages']), 200);
    }

    /**
     * @OA\Delete(
     *     path="/application/tickets/{ticket}",
     *     summary="Delete/Close an existing support ticket",
     *     tags={"Tickets"},
     *     @OA\Response(
     *         response=200,
     *         description="Ticket closed/deleted successfully"
     *     ),
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         description="ID or UUID of the ticket",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function destroy(SupportTicket $ticket)
    {
        if ($ticket->isClosed()) {
            try {
                foreach ($ticket->attachments as $attachment) {
                    \File::delete(storage_path('app/'.$attachment->path));
                }
                \File::deleteDirectory(storage_path("app/helpdesk/attachments/{$ticket->id}"));
            } catch (\Exception $e) {
                logger()->error($e->getMessage());
            }
            $ticket->attachments()->delete();
            $ticket->delete();
            return response()->json(['message' => 'Ticket deleted successfully'], 200);
        }
        
        $ticket->close('admin', auth('admin')->id());
        return response()->json(['message' => __('helpdesk.support.ticket_closed')], 200);
    }

    /**
     * @OA\Post(
     *     path="/application/tickets/{ticket}/reply",
     *     summary="Reply to a support ticket",
     *     tags={"Tickets"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="Thank you for contacting us...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reply added successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SupportTicket")
     *     ),
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         description="ID or UUID of the ticket",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function reply(ReplyTicketRequest $request, SupportTicket $ticket)
    {
        $ticket->addMessage($request->get('content'), null, auth('admin')->id());
        
        foreach ($request->file('attachments', []) as $attachment) {
            $ticket->addAttachment($attachment, $ticket->customer_id, auth('admin')->id());
        }
        
        return response()->json([
            'message' => __('helpdesk.support.ticket_replied'),
            'ticket' => $ticket->load(['customer', 'department', 'messages']),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/application/tickets/{ticket}/close",
     *     summary="Close a support ticket",
     *     tags={"Tickets"},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", nullable=true, example="Issue resolved")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket closed successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SupportTicket")
     *     ),
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         description="ID or UUID of the ticket",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function close(Request $request, SupportTicket $ticket)
    {
        if ($ticket->isClosed()) {
            return response()->json(['message' => 'Ticket is already closed'], 400);
        }
        
        $ticket->close('admin', auth('admin')->id(), $request->get('reason'));
        
        return response()->json([
            'message' => __('helpdesk.support.ticket_closed'),
            'ticket' => $ticket->fresh(['customer', 'department', 'messages']),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/application/tickets/{ticket}/reopen",
     *     summary="Reopen a closed support ticket",
     *     tags={"Tickets"},
     *     @OA\Response(
     *         response=200,
     *         description="Ticket reopened successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SupportTicket")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ticket is not closed"
     *     ),
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         description="ID or UUID of the ticket",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function reopen(SupportTicket $ticket)
    {
        if (!$ticket->isClosed()) {
            return response()->json(['message' => 'Ticket is not closed'], 400);
        }
        
        $ticket->reopen();
        
        return response()->json([
            'message' => __('helpdesk.support.ticket_reopened'),
            'ticket' => $ticket->fresh(['customer', 'department', 'messages']),
        ], 200);
    }
}
