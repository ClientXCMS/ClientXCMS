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

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $invoice_id
 * @property string $start_date
 * @property string|null $end_date
 * @property string|null $paid_at
 * @property float|null $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SubscriptionLog extends Model
{
    protected $fillable = [
        'subscription_id',
        'invoice_id',
        'paid_at',
        'amount',
    ];
}
