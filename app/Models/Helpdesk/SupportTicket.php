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
namespace App\Models\Helpdesk;

use App\Events\Helpdesk\HelpdeskTicketAnsweredCustomer;
use App\Events\Helpdesk\HelpdeskTicketAnsweredStaff;
use App\Events\Helpdesk\HelpdeskTicketClosedEvent;
use App\Events\Helpdesk\HelpdeskTicketCreatedEvent;
use App\Events\Helpdesk\HelpdeskTicketReopenEvent;
use App\Mail\Helpdesk\NotifyCustomerEmail;
use App\Mail\Helpdesk\NotifySubscriberEmail;
use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use App\Models\Billing\Invoice;
use App\Models\Provisioning\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * 
 *
 * @OA\Schema (
 *     schema="SupportTicket",
 *     title="Support Ticket",
 *     description="Support ticket model",
 *     required={"subject", "priority", "status", "department_id", "customer_id"},
 * 
 *     @OA\Property(property="id", type="integer", example=101),
 *     @OA\Property(property="subject", type="string", example="Problème de facturation"),
 *     @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="medium"),
 *     @OA\Property(property="status", type="string", enum={"open", "closed", "answered"}, example="open"),
 *     @OA\Property(property="department_id", type="integer", example=3),
 *     @OA\Property(property="customer_id", type="integer", example=5),
 *     @OA\Property(property="assigned_to", type="integer", nullable=true, example=12),
 *     @OA\Property(property="staff_subscribers", type="array", @OA\Items(type="integer"), example={1, 2, 3}),
 *     @OA\Property(property="closed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="close_reason", type="string", nullable=true, example="Demande résolue"),
 *     @OA\Property(property="closed_by", type="string", nullable=true, example="admin"),
 *     @OA\Property(property="closed_by_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="related_type", type="string", nullable=true, enum={"service", "invoice"}, example="service"),
 *     @OA\Property(property="related_id", type="integer", nullable=true, example=41),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="uuid", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="customer",
 *         ref="#/components/schemas/Customer"
 *     ),
 *     @OA\Property(
 *         property="assignedTo",
 *         ref="#/components/schemas/Admin"
 *     ),
 *     @OA\Property(
 *         property="department",
 *         ref="#/components/schemas/SupportDepartment"
 *     ),
 *     @OA\Property(
 *         property="attachments",
 *         type="array",
 * 
 *         @OA\Items(ref="#/components/schemas/SupportAttachment")
 *     ),
 * 
 *     @OA\Property(
 *         property="messages",
 *         type="array",
 * 
 *         @OA\Items(ref="#/components/schemas/SupportMessage")
 *     ),
 * 
 *     @OA\Property(
 *         property="comments",
 *         type="array",
 * 
 *         @OA\Items(ref="#/components/schemas/SupportComment")
 *     )
 * )
 * @property int $id
 * @property int $department_id
 * @property int $customer_id
 * @property string $subject
 * @property string $priority
 * @property string|null $related_type
 * @property int|null $related_id
 * @property array|null $staff_subscribers
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $close_reason
 * @property string|null $closed_by
 * @property int|null $closed_by_id
 * @property int|null $assigned_to
 * @property string $status
 * @property string|null $uuid
 * @property-read Admin|null $assignedTo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Helpdesk\SupportAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Helpdesk\SupportComment> $comments
 * @property-read int|null $comments_count
 * @property-read Customer $customer
 * @property-read \App\Models\Helpdesk\SupportDepartment $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Helpdesk\SupportMessage> $messages
 * @property-read int|null $messages_count
 * @method static \Database\Factories\Helpdesk\TicketFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereCloseReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereClosedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereClosedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereRelatedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereRelatedType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereStaffSubscribers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket withoutTrashed()
 * @mixin \Eloquent
 */
class SupportTicket extends Model
{
    use HasFactory, softDeletes;

    const STATUS_OPEN = 'open';

    const STATUS_CLOSED = 'closed';

    const STATUS_ANSWERED = 'answered';

    const FILTERS = [
        'all' => 'all',
        self::STATUS_OPEN => 'open',
        self::STATUS_CLOSED => 'closed',
        self::STATUS_ANSWERED => 'answered',
    ];

    const PRIORITIES = [
        'low' => 'low',
        'medium' => 'medium',
        'high' => 'high',
    ];

    protected $fillable = [
        'department_id',
        'customer_id',
        'status',
        'priority',
        'subject',
        'related_type',
        'related_id',
        'staff_subscribers',
        'closed_at',
        'close_reason',
        'closed_by',
        'closed_by_id',
        'assigned_to',
        'uuid',
    ];

    protected $casts = [
        'staff_subscribers' => 'array',
        'closed_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($ticket) {
            $ticket->attachments()->delete();
            $ticket->messages()->delete();
            $ticket->comments()->delete();
        });

        static::creating(function ($ticket) {
            $ticket->uuid = Str::uuid();
        });
    }

    public function staffCanView(Admin $admin)
    {
        return $admin->can('admin.manage_tickets_department.'.$this->department_id);
    }

    public function comments()
    {
        return $this->hasMany(SupportComment::class, 'ticket_id')->orderBy('created_at', 'desc');
    }

    public static function getPriorities()
    {
        return collect(self::PRIORITIES)->mapWithKeys(function ($value, $key) {
            return [$key => __('helpdesk.priorities.'.$key)];
        });
    }

    public function priorityLabel()
    {
        return __('helpdesk.priorities.'.$this->priority);
    }

    public function assignedTo()
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function department()
    {
        return $this->belongsTo(SupportDepartment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function attachments()
    {
        return $this->hasMany(SupportAttachment::class, 'ticket_id');
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    public function related()
    {
        $related = null;
        if ($this->related_type == 'service') {
            $related = $this->belongsTo(Service::class, 'related_id');
        }
        if ($this->related_type == 'invoice') {
            $related = $this->belongsTo(Invoice::class, 'related_id');
        }

        return $related;
    }

    public function isValidRelated()
    {
        if ($this->related_type == 'service') {
            return Service::where('id', $this->related_id)->exists();
        }
        if ($this->related_type == 'invoice') {
            return Invoice::where('id', $this->related_id)->exists();
        }

        return false;
    }

    public function notifySubscriber(Admin $subscriber, string $message, bool $firstMessage)
    {
        $subscriber->notify(new NotifySubscriberEmail($this, $message, $firstMessage));
    }

    public function notifyCustomer(string $message)
    {
        $this->customer->notify(new NotifyCustomerEmail($this, $message));
    }

    public function addMessage(string $content, ?int $customerId = null, ?int $staffId = null)
    {
        $isSpam = false;
        $lastMessage = $this->messages()->latest()->first();
        if ($lastMessage != null) {
            /** @var Carbon $createdAt */
            $createdAt = $lastMessage->created_at;
            if ($createdAt->diffInSeconds() < 10) {
                $isSpam = true;
            }
        }
        if ($isSpam) {
            return;
        }
        $message = new SupportMessage;
        $message->fill([
            'message' => $content,
            'customer_id' => $customerId,
            'admin_id' => $staffId,
        ]);
        $firstMessage = $this->messages()->count() == 0;
        $this->messages()->save($message);
        if ($customerId != null) {
            $subscribers = $this->department->staff_subscribers ?? [];
            $subscribers = array_merge($subscribers, $this->staff_subscribers ?? []);
            $subscribers = array_unique($subscribers);
            foreach ($subscribers as $subscriber) {
                $this->notifySubscriber($subscriber, $message, $firstMessage);
            }
        } else {
            $this->notifyCustomer($message);
        }
        if ($firstMessage) {
            event(new HelpdeskTicketCreatedEvent($this, $message));
        } else {
            if ($customerId != null) {
                event(new HelpdeskTicketAnsweredCustomer($this, $message));
                $this->update(['status' => self::STATUS_OPEN]);
                $message->update(['read_at' => now()]);
            } else {
                $this->update(['status' => self::STATUS_ANSWERED]);
                event(new HelpdeskTicketAnsweredStaff($this, $message));
            }
        }
    }

    public function readMessages()
    {
        $this->messages()->where('admin_id', '!=', null)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function excerptSubject(int $length = 24)
    {
        return Str::limit($this->subject, $length);
    }

    public function attachedUsers()
    {
        $users = [];
        foreach ($this->messages as $message) {
            if ($message->customer_id != null) {
                $initial = $message->customer->firstname[0].$message->customer->lastname[0];
                $users[$initial] = $message->customer->excerptFullName();
            }
            if ($message->admin_id != null) {
                $initial = $message->admin->firstname[0].$message->admin->lastname[0];
                $users[$initial] = $message->admin->username;
            }
        }

        return $users;

    }

    public function isOpen()
    {
        return $this->status == self::STATUS_OPEN || $this->status == self::STATUS_ANSWERED;
    }

    public function isClosed()
    {
        return $this->status == self::STATUS_CLOSED;
    }

    public function close(string $closedBy, int $closedById, ?string $reason = null)
    {
        $this->status = self::STATUS_CLOSED;
        $this->closed_at = now();
        $this->close_reason = $reason;
        $this->closed_by = $closedBy;
        $this->closed_by_id = $closedById;
        $this->save();
        event(new HelpdeskTicketClosedEvent($this));
    }

    public function reopen()
    {
        $this->status = self::STATUS_OPEN;
        $this->closed_at = null;
        $this->close_reason = null;
        $this->closed_by = null;
        $this->closed_by_id = null;
        $this->save();
        event(new HelpdeskTicketReopenEvent($this));
    }

    public function reply(string $content)
    {
        $this->addMessage($content, auth()->id());
    }

    public function relatedValue()
    {
        if ($this->related_id == null) {
            return null;
        }

        return "{$this->related_type}-{$this->related_id}";
    }

    public function addAttachment(UploadedFile $attachment, ?int $customerId = null, ?int $staffId = null)
    {
        $lastMessage = $this->messages()->latest()->first();
        $folder = "helpdesk/attachments/{$this->id}/";
        $attachmentName = $attachment->getClientOriginalName();
        $attachmentName = str_replace(' ', '_', $attachmentName);
        $attachmentName = rand(1000, 9999).'_'.$attachmentName;
        $attachment->storeAs($folder, $attachmentName);
        $file = new SupportAttachment;
        $file->fill([
            'filename' => $attachment->getClientOriginalName(),
            'path' => 'helpdesk/attachments/'.$this->id.'/'.$attachmentName,
            'mime' => $attachment->getClientMimeType(),
            'size' => $attachment->getSize(),
            'ticket_id' => $this->id,
            'customer_id' => $customerId,
            'admin_id' => $staffId,
            'message_id' => $lastMessage->id ?? null,
        ]);
        $file->save();
    }

    protected static function newFactory()
    {
        return \Database\Factories\Helpdesk\TicketFactory::new();
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (Str::isUuid($value)) {
            return $this->where('uuid', $value)->firstOrFail();
        }

        return $this->where('id', $value)->firstOrFail();
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
