<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Models\Helpdesk;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * v2.16 — Canned reply / macro for the helpdesk.
 *
 * Macros can be scoped to a list of departments (JSON `department_ids`).
 * `shortcut` is an optional inline token (e.g. `:welcome`) the editor
 * can autocomplete; `use_count` is incremented every time the macro is
 * inserted so admins see which ones are popular.
 *
 * Variable expansion happens at render time via expandFor() which
 * supports %fullname%, %email%, %service_name%, %service_uuid%,
 * %ticket_id%, %ticket_subject%, %app_name%.
 *
 * @property int $id
 * @property string $name
 * @property string|null $shortcut
 * @property string $content
 * @property array|null $department_ids
 * @property int $use_count
 * @property bool $enabled
 * @property int|null $created_by_id
 */
class SupportMacro extends Model
{
    use HasFactory;

    protected $table = 'support_macros';

    protected $fillable = [
        'name',
        'shortcut',
        'content',
        'department_ids',
        'use_count',
        'enabled',
        'created_by_id',
    ];

    protected $casts = [
        'department_ids' => 'array',
        'enabled' => 'boolean',
        'use_count' => 'integer',
    ];

    protected $attributes = [
        'enabled' => true,
        'use_count' => 0,
    ];

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function scopeForDepartment(Builder $query, ?int $departmentId): Builder
    {
        if ($departmentId === null) {
            return $query;
        }
        return $query->where(function (Builder $q) use ($departmentId) {
            $q->whereNull('department_ids')
                ->orWhereJsonContains('department_ids', $departmentId);
        });
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    /**
     * Render the macro for a specific ticket, expanding the supported
     * placeholders. Returns the markdown ready to drop into the editor.
     */
    public function expandFor(?SupportTicket $ticket): string
    {
        $context = [
            'app_name' => (string) config('app.name'),
            'ticket_id' => $ticket?->id ?? '',
            'ticket_subject' => (string) ($ticket?->subject ?? ''),
            'fullname' => (string) ($ticket?->customer?->fullName ?? ''),
            'email' => (string) ($ticket?->customer?->email ?? ''),
            'service_name' => '',
            'service_uuid' => '',
        ];

        if ($ticket && $ticket->related_type === 'service' && $ticket->related_id) {
            $service = \App\Models\Provisioning\Service::find($ticket->related_id);
            if ($service !== null) {
                $context['service_name'] = (string) $service->name;
                $context['service_uuid'] = (string) $service->uuid;
            }
        }

        $rendered = $this->content;
        foreach ($context as $key => $value) {
            $rendered = str_replace('%' . $key . '%', $value, $rendered);
        }

        return $rendered;
    }

    public function bumpUsage(): void
    {
        $this->increment('use_count');
    }
}
