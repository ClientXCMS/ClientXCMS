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

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * 
 *
 * @OA\Schema (
 *     schema="SupportMessage",
 *     title="Support Message",
 *     description="Message sent on a support ticket",
 *     required={"ticket_id", "message"},
 * 
 *     @OA\Property(property="id", type="integer", example=12),
 *     @OA\Property(property="ticket_id", type="integer", example=101),
 *     @OA\Property(property="customer_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="admin_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="message", type="string", example="Hello i have problem."),
 *     @OA\Property(property="read_at", type="string", format="date-time", nullable=true, example="2024-04-13T15:30:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-04-12T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-12T10:15:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="customer",
 *         ref="#/components/schemas/Customer"
 *     ),
 *     @OA\Property(
 *         property="admin",
 *         ref="#/components/schemas/Admin"
 *     )
 * )
 * @property int $id
 * @property int $ticket_id
 * @property int|null $customer_id
 * @property int|null $admin_id
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $edited_at
 * @property-read Admin|null $admin
 * @property-read Customer|null $customer
 * @property-read \App\Models\Helpdesk\SupportTicket|null $ticket
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage whereEditedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportMessage withoutTrashed()
 * @mixin \Eloquent
 */
class SupportMessage extends Model
{
    use HasFactory, softDeletes;

    protected $fillable = [
        'ticket_id',
        'customer_id',
        'admin_id',
        'message',
        'read_at',
        'edited_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    protected $with = ['customer', 'admin'];

    public function formattedMessage()
    {
        $parser = new \Parsedown;
        $parser->setSafeMode(true);

        return nl2br($parser->parse($this->message));
    }

    public function containerClasses(string $view = 'customer')
    {
        if ($view === 'customer') {
            return $this->customer_id != null ? 'max-w-lg flex gap-x-2 sm:gap-x-4' : 'max-w-lg ms-auto flex justify-end gap-x-2 sm:gap-x-4';
        }
        if ($view === 'admin') {
            return $this->admin_id != null ? 'max-w-lg ms-auto flex justify-end gap-x-2 sm:gap-x-4 max-w-lg' : 'max-w-lg flex gap-x-2 sm:gap-x-4';
        }
    }

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function isStaff()
    {
        return $this->admin_id !== null;
    }

    public function isCustomer()
    {
        return $this->customer_id !== null;
    }

    public function staffUsername()
    {
        if ($this->admin === null) {
            return 'Deleted Staff';
        }

        return $this->admin->username;
    }

    public function initials()
    {
        if ($this->customer_id != null) {
            return $this->customer->initials();
        }
        if ($this->admin_id != null) {
            if ($this->admin != null) {
                return $this->admin->initials();
            }
        }
        return 'AB';
    }

    public function canEdit()
    {
        if ($this->created_at->diffInMinutes() > 15) {
            return false;
        }
        if ($this->customer_id != null && auth('web')->id() != null) {
            return $this->customer_id == auth('web')->id();
        }

        return $this->admin_id == auth('admin')->id();
    }

    public function replyText(int $i, string $view = 'customer')
    {
        if ($this->customer_id != null) {
            if ($i == 0) {
                if ($view === 'customer') {
                    return __('helpdesk.support.show.your_demand');
                }

                return __('helpdesk.support.show.customer_demand', ['app' => setting('app_name')]);
            }
            if ($view === 'customer') {
                return __('helpdesk.support.show.replybycustomer1');
            }

            return __('helpdesk.support.show.replybycustomer2');
        }
        if ($i == 0) {
            return __('helpdesk.support.show.messagebystaff', ['app' => setting('app_name')]);
        }

        return __('helpdesk.support.show.replybystaff', ['app' => setting('app_name')]);
    }

    public function getAttachments(Collection $collection)
    {
        return $collection->where('message_id', $this->id);
    }

    public function hasAttachments(Collection $collection)
    {
        return $this->getAttachments($collection)->count() > 0;
    }

    public function getAttachmentsNames(Collection $collection)
    {
        return $this->getAttachments($collection)->pluck('file_name')->toArray();
    }
}
