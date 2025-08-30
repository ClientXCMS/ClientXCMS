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


namespace App\Models\Provisioning;

use App\DTO\Store\ConfigOptionDTO;
use App\Models\Billing\ConfigOption;
use App\Models\Billing\Traits\PricingInteractTrait;
use Carbon\Carbon;

/**
 * 
 *
 * @property int $id
 * @property int $config_option_id
 * @property int $service_id
 * @property string|null $value
 * @property string|null $key
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ConfigOption $option
 * @property-read \App\Models\Provisioning\Service $service
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionService query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionService whereConfigOptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionService whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionService whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionService whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionService whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOptionService whereValue($value)
 * @mixin \Eloquent
 */
class ConfigOptionService extends \Illuminate\Database\Eloquent\Model
{
    use PricingInteractTrait;

    protected string $pricing_key = 'config_options_service';

    protected $table = 'config_options_services';

    protected $fillable = [
        'config_option_id',
        'service_id',
        'value',
        'key',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $attributes = [
        'value' => '',
        'key' => '',
        'expires_at' => null,
    ];

    public function option()
    {
        return $this->belongsTo(ConfigOption::class, 'config_option_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function renew(?string $expires_at = null)
    {
        $this->update([
            'expires_at' => Carbon::createFromFormat('d/m/y H:i:s', $expires_at ?? $this->expires_at),
        ]);
    }

    public function formattedPrice(string $currency)
    {
        $tmp = '';
        $pricing = $this->getPriceByCurrency($currency);
        if ($pricing->recurringPayment() != 0) {
            $tmp .= formatted_price($pricing->recurringPayment(), $currency).' / '.__('store.recurring_price');
        }
        if ($pricing->onetimePayment() > 0) {
            $tmp .= ($tmp ? ' ' : '').formatted_price($pricing->onetimePayment(), $currency).' / '.__('store.config.onetime_payment');
        }
        if ($pricing->setup() > 0) {
            $tmp .= ($tmp ? ' ' : '').formatted_price($pricing->setup(), $currency).' / '.__('store.setup_price');
        }

        return $tmp;
    }

    public function formattedValue()
    {
        return \Str::limit((new ConfigOptionDTO($this->option, $this->value, $this->expires_at, false))->formattedName(), 40);
    }
}
