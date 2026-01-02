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

namespace App\Http\Controllers\Admin\Helpdesk\Support;

use App\Core\Admin\Dashboard\AdminCountWidget;
use App\Http\Requests\Helpdesk\ReplyTicketRequest;
use App\Http\Requests\Helpdesk\SubmitTicketRequest;
use App\Http\Requests\Helpdesk\UpdateTicketRequest;
use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportComment;
use App\Models\Helpdesk\SupportMessage;
use App\Models\Helpdesk\SupportTicket;
use App\Services\Helpdesk\TicketStatisticsService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class TicketController extends \App\Http\Controllers\Admin\AbstractCrudController
{
    protected string $viewPath = 'admin.helpdesk.tickets';

    protected string $routePath = 'admin.helpdesk.tickets';

    protected string $translatePrefix = 'helpdesk.admin.tickets';

    protected string $model = \App\Models\Helpdesk\SupportTicket::class;

    protected string $searchField = 'subject';

    protected string $filterField = 'department_id';

    protected array $relations = ['customer', 'messages'];

    public function getIndexFilters()
    {
        $priorities = SupportTicket::PRIORITIES;
        $prioritiesArray = collect($priorities)->mapWithKeys(function ($key) {
            return [$key => [__('helpdesk.priorities.'.$key), 'priority']];
        })->toArray();

        return \App\Models\Helpdesk\SupportDepartment::all()->pluck('name', 'id')->toArray() + $prioritiesArray;
    }

    public function getIndexParams($items, string $translatePrefix): array
    {
        $params = parent::getIndexParams($items, $translatePrefix);
        $ticketStatsService = new TicketStatisticsService;
        $pending_tickets = SupportTicket::where('status', SupportTicket::STATUS_OPEN)->count();
        $active_tickets = SupportTicket::whereIn('status', [SupportTicket::STATUS_ANSWERED, SupportTicket::STATUS_OPEN])->count();
        $total_tickets = SupportTicket::count();
        $tickets_last_week = SupportTicket::where('created_at', '>=', now()->subWeek())->count();
        $widgets = collect();
        $stats = $ticketStatsService->getClosedTicketStats();

        $widgets->push(new AdminCountWidget('pending_tickets', 'bi bi-ticket-detailed', 'helpdesk.admin.widgets.pending_tickets', $pending_tickets, true));
        $widgets->push(new AdminCountWidget('active_tickets', 'bi bi-headset', 'helpdesk.admin.widgets.active_tickets', $active_tickets, true));
        $widgets->push(new AdminCountWidget('total_tickets', 'bi bi-ticket', 'helpdesk.admin.widgets.total_tickets', $total_tickets, true));
        $widgets->push(new AdminCountWidget('tickets_last_week', 'bi bi-calendar-week', 'helpdesk.admin.widgets.tickets_last_week', $tickets_last_week, true));
        $widgets->push(new AdminCountWidget('avg_reply_time', 'bi bi-clock-history', 'helpdesk.admin.widgets.avg_reply_time', $stats['avg_reply_time'], true, true));
        $widgets->push(new AdminCountWidget('avg_resolution_time', 'bi bi-stopwatch', 'helpdesk.admin.widgets.avg_resolution_time', $stats['avg_resolution_time'], true, true));

        $params['helpdesk_widgets'] = $widgets;
        $params['tickets_to_reply'] = $ticketStatsService->getTicketsToReply();
        $params['active_tickets'] = $ticketStatsService->getActiveTickets();
        $params['staff_message_counts'] = $ticketStatsService->getStaffMessageCounts();
        $params['department_ticket_counts'] = $ticketStatsService->getDepartmentTicketCounts();
        $params['graph_labels'] = $ticketStatsService->getWeeklyGraphLabels();
        $params['graph_data'] = $ticketStatsService->getWeeklyGraphData();

        return $params;
    }

    protected function queryIndex(): LengthAwarePaginator
    {
        return QueryBuilder::for($this->model)
            ->allowedFilters(array_merge(array_keys($this->getSearchFields()), [$this->filterField, 'priority']))
            ->allowedSorts($this->sorts)
            ->with($this->relations)
            ->orderBy('created_at', 'desc')
            ->where('status', SupportTicket::STATUS_CLOSED)
            ->paginate($this->perPage)
            ->appends(request()->query());
    }

    protected function filterIndex(string $filter)
    {
        $departments = collect(\App\Models\Helpdesk\SupportDepartment::all())->pluck('id')->toArray();
        $priorities = array_keys(SupportTicket::PRIORITIES);

        if (in_array($filter, $departments)) {
            return $this->model::where('department_id', $filter)->orderBy('created_at', 'desc')->paginate($this->perPage);
        }
        if (in_array($filter, $priorities)) {
            return $this->model::where('priority', $filter)->orderBy('created_at', 'desc')->paginate($this->perPage);
        }

        return parent::filterIndex($filter);
    }

    public function getCreateParams()
    {
        $data = parent::getCreateParams();
        $data['departments'] = \App\Models\Helpdesk\SupportDepartment::all();
        if (\request()->query->has('department_id')) {
            $data['currentdepartment'] = \request()->query('department_id');
        } else {
            $data['currentdepartment'] = $data['departments']->first()->id ?? null;
        }
        $customerId = \request()->query('customer_id');
        $departmentId = \request()->query('department_id');
        if ($data['departments']->contains('id', $departmentId)) {
            $data['currentdepartment'] = $departmentId;
        }
        $customer = Customer::find($customerId);
        if ($customerId) {
            if ($customer == null) {
                $data['related'] = [];
                \Session::flash('error', __('helpdesk.admin.tickets.customer_not_found'));
            } else {
                $data['related'] = $customer->supportRelatedItems();
            }
            $data['priorities'] = SupportTicket::getPriorities();
            $data['customer'] = $customer;
        }
        $data['currentCustomer'] = (bool) $customerId;
        $data['item']->customer_id = $customerId;

        return $data;
    }

    public function show(SupportTicket $ticket)
    {
        $this->checkPermission('show', $ticket);
        abort_if(! $ticket->staffCanView(auth('admin')->user()), 403);
        $data['ticket'] = $ticket;
        $data['item'] = $ticket;
        $data['related'] = $ticket->customer->supportRelatedItems();
        $data['priorities'] = SupportTicket::getPriorities();
        $data['staffs'] = \App\Models\Admin\Admin::all()->filter(function ($staff) use ($ticket) {
            return $staff->can('admin.manage_tickets_department.'.$ticket->department_id);
        })->pluck('fullname', 'id');
        $data['staffs']->put('none', __('global.none'));
        $data['departments'] = \App\Models\Helpdesk\SupportDepartment::all()->pluck('name', 'id')->toArray();

        return $this->showView($data);
    }

    public function addComment(Request $request, SupportTicket $ticket)
    {
        abort_if(! $ticket->staffCanView(auth('admin')->user()), 403);
        $this->checkPermission('reply', $ticket);
        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);
        $ticket->comments()->create([
            'comment' => $validated['comment'],
            'admin_id' => auth('admin')->id(),
        ]);

        return redirect()->route($this->routePath.'.show', $ticket)->with('success', __('helpdesk.support.show.comments.added'));
    }

    public function deleteComment(Request $request, SupportTicket $ticket, SupportComment $comment)
    {
        abort_if(! $ticket->staffCanView(auth('admin')->user()), 403);
        $this->checkPermission('reply', $ticket);
        $comment->delete();

        return redirect()->route($this->routePath.'.show', $ticket)->with('success', __('helpdesk.support.show.comments.deleted'));
    }

    public function destroy(SupportTicket $ticket)
    {
        $this->checkPermission('delete', $ticket);
        abort_if(! $ticket->staffCanView(auth('admin')->user()), 403);
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

            return $this->deleteRedirect($ticket);
        }
        $ticket->close('admin', auth('admin')->id());

        return redirect()->route($this->routePath.'.index')->with('success', __('helpdesk.support.ticket_closed'));
    }

    public function reply(ReplyTicketRequest $request, SupportTicket $ticket)
    {
        $this->checkPermission('reply', $ticket);
        abort_if(! $ticket->staffCanView(auth('admin')->user()), 403);
        $ticket->addMessage($request->get('content'), null, auth('admin')->id());
        foreach ($request->file('attachments', []) as $attachment) {
            $ticket->addAttachment($attachment, $ticket->customer_id, auth('admin')->id());
        }
        if ($request->has('close')) {
            $ticket->close('admin', auth('admin')->id());

            return redirect()->route($this->routePath.'.index')->with('success', __('helpdesk.support.ticket_closed'));
        }

        return redirect()->route($this->routePath.'.show', $ticket)->with('success', __('helpdesk.support.ticket_replied'));
    }

    public function close(Request $request, SupportTicket $ticket)
    {
        $response = $this->destroy($ticket);
        if ($request->get('reason')) {
            $ticket->update(['close_reason' => $request->get('reason')]);
        }

        return $response;
    }

    protected function getSearchFields()
    {
        return [
            'id' => 'Identifier',
            'customer.email' => __('global.customer'),
            'subject' => __('helpdesk.subject'),
            'uuid' => 'UUID',
        ];
    }

    public function updateMessage(Request $request, SupportTicket $ticket, SupportMessage $message)
    {
        abort_if(! $ticket->staffCanView(auth('admin')->user()), 403);
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
        ]);
        if ($message->admin_id != auth('admin')->id() || $ticket->id != $message->ticket_id || $message->isCustomer()) {
            return redirect()->route($this->routePath.'.show', $ticket)->with('error', 'You are not allowed to edit this message');
        }
        $message->update(['message' => $validated['content'], 'edited_at' => Carbon::now()]);

        return redirect()->route($this->routePath.'.show', $ticket)->with('success', __('helpdesk.support.message_updated'));
    }

    public function destroyMessage(SupportTicket $ticket, SupportMessage $message): \Illuminate\Http\RedirectResponse
    {
        abort_if(! $ticket->staffCanView(auth('admin')->user()), 403);
        if ($message->admin_id != auth('admin')->id() || $ticket->id != $message->ticket_id || $message->isCustomer()) {
            return redirect()->route($this->routePath.'.show', $ticket)->with('error', 'You are not allowed to destroy this message');
        }
        $message->delete();

        return redirect()->route($this->routePath.'.show', $ticket)->with('success', __($this->flashs['deleted']));
    }

    public function reopen(SupportTicket $ticket)
    {
        $this->checkPermission('update', $ticket);
        $ticket->reopen();

        return redirect()->route($this->routePath.'.show', $ticket)->with('success', __('helpdesk.support.ticket_reopened'));
    }

    public function update(UpdateTicketRequest $request, SupportTicket $ticket)
    {
        $this->checkPermission('update', $ticket);
        $ticket = $request->update();

        return $this->updateRedirect($ticket);
    }

    public function store(SubmitTicketRequest $request)
    {
        $this->checkPermission('create');
        $validated = $request->validated();
        if ($request->query->has('customer_id') && ! Customer::find($request->query->get('customer_id'))) {
            return redirect()->route($this->routePath.'.create')->with('error', __('helpdesk.tickets.customer_not_found'));
        }
        $validated['customer_id'] = $request->query->get('customer_id');
        $ticket = SupportTicket::create($validated);
        $ticket->addMessage($validated['content'], null, auth('admin')->id());
        foreach ($request->file('attachments', []) as $attachment) {
            $ticket->addAttachment($attachment, null, auth('web')->id());
        }

        return redirect()->route($this->routePath.'.show', $ticket)->with('success', __('global.created'));
    }

    public function download(SupportTicket $ticket, $attachment)
    {
        $this->checkPermission('show', $ticket);
        $attachment = $ticket->attachments()->where('id', $attachment)->first();
        abort_if(! $attachment, 404);

        return response()->download(storage_path("app/{$attachment->path}"), $attachment->name);
    }

    protected function getPermissions(string $tablename)
    {
        $tablename = 'tickets';

        return [
            'showAny' => [
                'admin.manage_'.$tablename,
            ],
            'show' => [
                'admin.manage_'.$tablename,
            ],
            'update' => [
                'admin.manage_'.$tablename,
            ],
            'delete' => [
                'admin.close_'.$tablename,
            ],
            'create' => [
                'admin.manage_'.$tablename,
            ],
            'reply' => [
                'admin.reply_'.$tablename,
            ],
        ];
    }

    protected function beforePermissionCheck(?Model $model = null)
    {
        $check = parent::beforePermissionCheck($model);
        if ($model && $model->staffCanView(auth('admin')->user())) {
            return true;
        }

        return $check;
    }
}
