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


namespace App\Http\Controllers\Front\Helpdesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Helpdesk\ReplyTicketRequest;
use App\Http\Requests\Helpdesk\SubmitTicketRequest;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportMessage;
use App\Models\Helpdesk\SupportTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('filter')) {
            $filter = $request->get('filter');
            if (! in_array($filter, array_keys(SupportTicket::FILTERS))) {
                return redirect()->route('front.support.index');
            }
            $tickets = SupportTicket::where('customer_id', auth()->id())->where('status', $request->get('filter'))->orderBy('created_at', 'desc')->paginate(10);
        } else {
            $filter = null;
            $tickets = SupportTicket::where('customer_id', auth()->id())->orderBy('created_at', 'desc')->paginate(10);
        }
        $filters = SupportTicket::FILTERS;

        return view('front.helpdesk.support.index', compact('tickets', 'filter', 'filters'));
    }

    public function create(Request $request)
    {
        $departments = SupportDepartment::all();
        $priorities = SupportTicket::getPriorities();
        $related = auth()->user()->supportRelatedItems();
        $currentdepartment = $request->query('department') ?? null;
        $subject = $request->query('subject') ?? null;
        $content = $request->query('content') ?? null;
        $priority = $request->query('priority') ?? null;
        $related_id = $request->query('related_id') ?? 'none';
        if ($currentdepartment) {
            if (! $departments->contains('id', $currentdepartment)) {
                return redirect()->route('front.support.create');
            }
        } else {
            $currentdepartment = $departments->first()->id ?? null;
        }
        $data = compact('departments', 'priorities', 'related', 'currentdepartment', 'subject', 'content', 'priority', 'related_id');

        return view('front.helpdesk.support.create', $data);
    }

    public function store(SubmitTicketRequest $request)
    {
        $ticket = new SupportTicket;
        $ticket->fill($request->only(['department_id', 'priority', 'subject', 'related_id', 'related_type']));
        $ticket->customer_id = auth()->id();
        $ticket->status = SupportTicket::STATUS_OPEN;
        $ticket->save();
        $ticket->addMessage($request->get('content'), auth()->id());
        foreach ($request->file('attachments', []) as $attachment) {
            $ticket->addAttachment($attachment, auth('web')->id());
        }

        return redirect()->route('front.support.index');
    }

    public function show(SupportTicket $ticket)
    {
        abort_if($ticket->customer_id != auth()->id(), 404);
        $ticket->readMessages();

        return view('front.helpdesk.support.show', compact('ticket'));
    }

    public function close(SupportTicket $ticket)
    {
        abort_if($ticket->customer_id != auth()->id(), 404);
        $ticket->close('customer', $ticket->customer_id);

        return redirect()->route('front.support.index')->with('success', __('helpdesk.support.ticket_closed'));
    }

    public function reopen(SupportTicket $ticket)
    {
        abort_if($ticket->customer_id != auth()->id(), 404);
        $days = setting('support_ticket_reopen_days', 7);
        if ($ticket->closed_at->diffInDays(now()) > $days && $days > 0) {
            return back()->with('error', __('helpdesk.support.ticket_reopen_days', ['days' => $days]));
        }
        $ticket->reopen();

        return redirect()->route('front.support.index')->with('success', __('helpdesk.support.ticket_reopened'));
    }

    public function reply(ReplyTicketRequest $request, SupportTicket $ticket)
    {
        abort_if($ticket->customer_id != auth()->id(), 404);
        if ($ticket->isClosed()) {
            return back()->with('error', __('helpdesk.support.ticket_closed_reply'));
        }
        $ticket->addMessage($request->get('content'), auth()->id());
        foreach ($request->file('attachments', []) as $attachment) {
            $ticket->addAttachment($attachment, auth('web')->id());
        }
        if (array_key_exists('close', $request->all())) {
            $ticket->close('customer', $ticket->customer_id);

            return redirect()->route('front.support.index')->with('success', __('helpdesk.support.ticket_closed'));
        }

        return back()->with('success', __('helpdesk.support.ticket_replied'));
    }

    public function download(SupportTicket $ticket, $attachment)
    {
        $attachment = $ticket->attachments()->where('id', $attachment)->first();
        abort_if(! $attachment, 404);
        if ($attachment->ticket->customer_id != auth('web')->id() || $ticket->id != $attachment->ticket_id) {
            abort(404);
        }

        return response()->download(storage_path('app/'.$attachment->path), $attachment->name);
    }

    public function updateMessage(Request $request, SupportTicket $ticket, SupportMessage $message)
    {
        abort_if($ticket->customer_id != auth()->id(), 404);
        abort_if($message->customer_id != auth('web')->id(), 404);
        if (! $message->isCustomer()) {
            return back()->with('error', __('helpdesk.support.ticket_message_cannot_edit'));
        }
        $validated = $request->validate([
            'content' => 'required|string',
        ]);
        $message->update(['message' => $request->get('content'), 'edited_at' => Carbon::now()]);

        return back()->with('success', __('helpdesk.support.ticket_replied'));
    }

    public function destroyMessage(SupportTicket $ticket, SupportMessage $message)
    {
        abort_if($ticket->customer_id != auth()->id(), 404);
        abort_if($message->customer_id != auth('web')->id(), 404);
        $message->delete();

        return back()->with('success', __('helpdesk.support.ticket_message_deleted'));
    }
}
