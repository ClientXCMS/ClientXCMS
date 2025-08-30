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

/**
 * 
 *
 * @property int $config_option_id
 * @property int $product_id
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Billing\ConfigOption $option
 * @property-read \App\Models\Store\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsProduct whereConfigOptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsProduct whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ConfigOptionsProduct extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'product_id',
        'config_option_id',
        'value',
    ];

    protected $with = ['option'];

    public function product()
    {
        return $this->belongsTo(\App\Models\Store\Product::class);
    }

    public function option()
    {
        return $this->belongsTo(ConfigOption::class, 'config_option_id');
    }
}
