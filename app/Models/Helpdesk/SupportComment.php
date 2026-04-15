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

namespace App\Models\Helpdesk;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema (
 *     schema="SupportComment",
 *     title="Support Comment",
 *     description="Internal comment on a support ticket",
 *     required={"ticket_id", "admin_id", "comment"},
 *
 *     @OA\Property(property="id", type="integer", example=14),
 *     @OA\Property(property="ticket_id", type="integer", example=101),
 *     @OA\Property(property="admin_id", type="integer", example=2),
 *     @OA\Property(property="comment", type="string", example="This is an internal comment."),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-04-14T11:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-14T12:00:00Z"),
 *     @OA\Property(
 *         property="staff",
 *         ref="#/components/schemas/Admin"
 *     )
 * )
 *
 * @property int $id
 * @property int $ticket_id
 * @property int $admin_id
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Admin $staff
 * @property-read \App\Models\Helpdesk\SupportTicket $ticket
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportComment whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportComment whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportComment whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SupportComment extends Model
{
    use HasFactory;

    protected $table = 'support_comments';

    protected $fillable = [
        'ticket_id',
        'admin_id',
        'comment',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function staff()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
