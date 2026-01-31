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

namespace App\Models\Billing;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $invoice_id
 * @property string $status
 * @property array $context
 * @property int|null $customer_id
 * @property int|null $staff_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Billing\Invoice $invoice
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog whereContext($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog whereStaffId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceLog withoutTrashed()
 *
 * @mixin \Eloquent
 */
class InvoiceLog extends Model
{
    use softDeletes;

    const PAY_INVOICE = 'pay_invoice';

    const CANCEL_INVOICE = 'cancel_invoice';

    const REFUND_INVOICE = 'refund_invoice';

    const DRAFT_INVOICE = 'draft_invoice';

    const SEND_INVOICE = 'send_invoice';

    const PAID_INVOICE = 'paid_invoice';

    const FAILED_INVOICE = 'failed_invoice';

    const PENDING_INVOICE = 'pending_invoice';

    const ADD_LINE = 'add_line';

    const REMOVE_LINE = 'remove_line';

    const DELETE_INVOICE = 'delete_invoice';

    const UPDATE_BALANCE = 'update_balance';

    protected $fillable = [
        'invoice_id',
        'status',
        'context',
        'customer_id',
        'staff_id',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function staff()
    {
        return $this->belongsTo(Admin::class, 'staff_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public static function log(Invoice $invoice, string $status, array $context = []): self
    {
        return self::create([
            'invoice_id' => $invoice->id,
            'status' => $status,
            'context' => $context,
            'customer_id' => auth('web')->id(),
            'staff_id' => auth('admin')->id(),
        ]);
    }
}
