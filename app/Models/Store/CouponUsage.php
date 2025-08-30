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


namespace App\Models\Store;

use App\Models\Account\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $coupon_id
 * @property int $customer_id
 * @property \Illuminate\Support\Carbon $used_at
 * @property float $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Store\Coupon $coupon
 * @property-read Customer $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponUsage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponUsage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponUsage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponUsage whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponUsage whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponUsage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponUsage whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponUsage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponUsage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponUsage whereUsedAt($value)
 * @mixin \Eloquent
 */
class CouponUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'customer_id',
        'used_at',
        'amount',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
