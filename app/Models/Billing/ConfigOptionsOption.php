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
namespace App\Models\Billing;

use App\Models\Billing\Traits\PricingInteractTrait;
use App\Models\Store\Pricing;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property int $config_option_id
 * @property string|null $friendly_name
 * @property string|null $value
 * @property int $sort_order
 * @property int $hidden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Billing\ConfigOption $configOption
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Pricing> $pricing
 * @property-read int|null $pricing_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption whereConfigOptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption whereFriendlyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption whereHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionsOption withoutTrashed()
 * @mixin \Eloquent
 */
class ConfigOptionsOption extends \Illuminate\Database\Eloquent\Model
{
    use PricingInteractTrait;
    use softDeletes;

    protected string $pricing_key = 'config_options_option';

    protected $fillable = [
        'config_option_id',
        'friendly_name',
        'value',
        'hidden',
        'sort_order',
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($option) {
            $option->pricing()->delete();
        });
    }

    public function configOption()
    {
        return $this->belongsTo(ConfigOption::class);
    }

    public function pricing()
    {
        return $this->hasMany(Pricing::class, 'related_id')->where('related_type', 'config_options_option');
    }
}
