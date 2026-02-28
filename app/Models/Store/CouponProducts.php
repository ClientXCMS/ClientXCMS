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

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $coupon_id
 * @property int $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponProducts newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponProducts newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponProducts query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponProducts whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponProducts whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponProducts whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponProducts whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponProducts whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CouponProducts extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'product_id',
    ];
}
