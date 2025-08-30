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

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @OA\Schema (
 *     schema="SupportAttachment",
 *     title="Support Attachment",
 *     description="A file attached to a support ticket message",
 *     required={"filename", "path", "mime", "size", "ticket_id"},
 * 
 *     @OA\Property(property="id", type="integer", example=102),
 *     @OA\Property(property="filename", type="string", example="screenshot.png"),
 *     @OA\Property(property="path", type="string", example="helpdesk/attachments/1234/8732_screenshot.png"),
 *     @OA\Property(property="mime", type="string", example="image/png"),
 *     @OA\Property(property="size", type="integer", description="Size in bytes", example=204800),
 *     @OA\Property(property="ticket_id", type="integer", example=12),
 *     @OA\Property(property="message_id", type="integer", nullable=true, example=87),
 *     @OA\Property(property="customer_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="admin_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-04-14T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-14T10:05:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 * )
 * @property int $id
 * @property string $filename
 * @property string $path
 * @property string $mime
 * @property int|null $customer_id
 * @property int|null $admin_id
 * @property int $size
 * @property int|null $message_id
 * @property int $ticket_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Helpdesk\SupportTicket $ticket
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportAttachment withoutTrashed()
 * @mixin \Eloquent
 */
class SupportAttachment extends Model
{
    use HasFactory, softDeletes;

    protected $fillable = [
        'filename',
        'path',
        'mime',
        'customer_id',
        'admin_id',
        'size',
        'ticket_id',
        'message_id',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class);
    }
}
