<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Http\Controllers\Front\Helpdesk;

use App\Http\Controllers\Controller;
use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportMessage;
use App\Models\Helpdesk\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * v2.16 — Tickets without an account.
 *
 * Visitors can open a ticket without registering. They give us their
 * email, the question and (optionally) a name. On submission we mint
 * a guest_token and redirect them to /support/track/{guest_token}
 * where they can read replies and answer back.
 *
 * If their email matches an existing customer, the ticket is linked
 * to that account so support sees the right context — but they keep
 * the guest token so they don't have to log in to follow up.
 *
 * Routes are bound to the `guest` middleware to avoid duplicating the
 * authenticated flow's UX accidentally.
 */
class GuestTicketController extends Controller
{
    public function create(): View
    {
        return view('front.helpdesk.guest.create', [
            'departments' => SupportDepartment::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'min:3', 'max:255'],
            'message' => ['required', 'string', 'min:5', 'max:10000'],
            'department_id' => ['nullable', Rule::exists('support_departments', 'id')],
        ]);

        $customer = Customer::where('email', strtolower($validated['email']))->first();
        $department = $validated['department_id']
            ?? SupportDepartment::query()->orderBy('id')->value('id');

        $ticket = SupportTicket::create([
            'department_id' => $department,
            'customer_id' => $customer?->id,
            'guest_email' => $customer ? null : $validated['email'],
            'guest_name' => $customer ? null : ($validated['name'] ?? null),
            'subject' => $validated['subject'],
            'status' => SupportTicket::STATUS_OPEN,
            'priority' => 'medium',
        ]);

        SupportMessage::create([
            'ticket_id' => $ticket->id,
            'customer_id' => $customer?->id,
            'message' => $validated['message'],
        ]);

        // Guests always receive the tracking URL — registered customers
        // also receive it (in case they're not logged in) but they can
        // additionally find the ticket inside /client/support.
        return redirect()->route('front.support.guest.track', ['token' => $ticket->guest_token])
            ->with('success', __('helpdesk.guest.submitted'));
    }

    public function track(string $token): View
    {
        $ticket = SupportTicket::with(['messages', 'customer'])->where('guest_token', $token)->firstOrFail();

        return view('front.helpdesk.guest.show', [
            'ticket' => $ticket,
            'token' => $token,
        ]);
    }

    public function reply(Request $request, string $token): RedirectResponse
    {
        $ticket = SupportTicket::where('guest_token', $token)->firstOrFail();
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:10000'],
        ]);

        SupportMessage::create([
            'ticket_id' => $ticket->id,
            'customer_id' => $ticket->customer_id,
            'message' => $validated['message'],
        ]);

        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            $ticket->update(['status' => SupportTicket::STATUS_OPEN, 'closed_at' => null]);
        }

        return redirect()->route('front.support.guest.track', ['token' => $token])
            ->with('success', __('helpdesk.guest.replied'));
    }
}
