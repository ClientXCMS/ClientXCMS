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
 * Year: 2025
 */
namespace App\Http\Requests\Helpdesk;

use App\Models\Helpdesk\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'department_id' => 'required|exists:support_departments,id',
            'priority' => 'required|in:'.implode(',', array_keys(SupportTicket::PRIORITIES)),
            'subject' => 'required|string|max:255',
            'close_reason' => 'nullable|string',
            'assigned_to' => 'nullable|exists:admins,id',
        ];
    }

    public function update(): SupportTicket
    {
        $ticket = $this->route('ticket');
        $ticket->update($this->validated());

        return $ticket;
    }

    public function prepareForValidation()
    {
        if ($this->has('assigned_to')) {
            $this->merge([
                'assigned_to' => $this->input('assigned_to') === 'none' ? null : $this->input('assigned_to'),
            ]);
        }
        if ($this->has('related_type')) {
            $this->merge([
                'related_type' => $this->input('related_type') === 'none' ? null : $this->input('related_type'),
                'related_id' => $this->input('related_type') === 'none' ? null : $this->input('related_id'),
            ]);
        }
    }
}
