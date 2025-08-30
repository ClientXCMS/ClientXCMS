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

use App\DTO\Store\ConfigOptionDTO;
use App\Models\Billing\Traits\PricingInteractTrait;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use App\Services\Store\PricingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property string $type
 * @property string $key
 * @property string $name
 * @property string|null $default_value
 * @property string|null $rules
 * @property int|null $min_value
 * @property int|null $max_value
 * @property int|null $step
 * @property bool $required
 * @property string|null $unit
 * @property bool $automatic
 * @property bool $hidden
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Billing\ConfigOptionsOption> $options
 * @property-read int|null $options_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Pricing> $pricing
 * @property-read int|null $pricing_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereAutomatic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereDefaultValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereMaxValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereMinValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereRules($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigOption withoutTrashed()
 * @mixin \Eloquent
 */
class ConfigOption extends Model
{
    use HasFactory;
    use PricingInteractTrait;
    use softDeletes;

    protected string $pricing_key = 'config_option';

    const TYPE_TEXT = 'text';

    const TYPE_CHECKBOX = 'checkbox';

    const TYPE_NUMBER = 'number';

    const TYPE_TEXTAREA = 'textarea';

    const TYPE_SLIDER = 'slider';

    const TYPE_RADIO = 'radio';

    const TYPE_DROPDOWN = 'dropdown';

    protected $with = ['options'];

    const MODE_NO_INVOICE = 'no_invoice';

    const MODE_INVOICE = 'invoice';

    protected $fillable = [
        'type',
        'key',
        'name',
        'rules',
        'min_value',
        'max_value',
        'step',
        'required',
        'default_value',
        'sort_order',
        'unit',
        'automatic',
        'hidden',
    ];

    protected $casts = [
        'required' => 'boolean',
        'automatic' => 'boolean',
        'hidden' => 'boolean',
    ];

    protected $attributes = [
        'step' => 1,
        'min_value' => 0,
        'max_value' => 100,
        'unit' => 'GB',
        'sort_order' => 0,
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($option) {
            $option->options()->delete();
            $option->products()->detach();
            $option->pricing()->delete();
            InvoiceItem::where('related_id', $option->id)
                ->where('type', 'config_option')
                ->whereNull('delivered_at')
                ->update(['delivered_at' => now()]);
        });
    }

    public static function getKeys()
    {
        $productTypes = app('extension')->getProductTypes();
        $supportedOptions = [];
        foreach ($productTypes as $productType) {
            if ($productType->server() === null) {
                continue;
            }
            foreach ($productType->server()->getSupportedOptions() as $key => $value) {
                $supportedOptions[$key]['types'][] = $productType->title();
                $supportedOptions[$key]['translation'] = $value;
            }
        }

        return collect($supportedOptions)->mapWithKeys(function ($option, $key) {
            return [$key => $option['translation'].' ('.implode(', ', $option['types']).')'];
        })->merge(['server_id' => __('provisioning.admin.configoptions.keys.server_id')])
            ->merge(['custom' => __('provisioning.admin.configoptions.keys.custom')]);

    }

    public static function getKeysForItems($items)
    {
        $productTypes = app('extension')->getProductTypes();
        $keys = $items->pluck('key')->unique();
        $supportedOptions = [];
        foreach ($productTypes as $productType) {
            if ($productType->server() === null) {
                continue;
            }
            $supportedOptions = array_merge($supportedOptions, $productType->server()->getSupportedOptions());
        }

        return $keys->mapWithKeys(function ($type) use ($supportedOptions) {
            return [$type => $supportedOptions[$type] ?? $type];
        });

    }

    public function options()
    {
        return $this->hasMany(ConfigOptionsOption::class)->orderBy('sort_order');
    }

    public function pricing()
    {
        return $this->hasMany(Pricing::class, 'related_id')->where('related_type', 'config_option');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'config_options_products');
    }

    public function render(array $options)
    {
        $options = collect($options)->mapWithKeys(function ($option) {
            return [$option->option->key => $option->value];
        })->toArray();

        return (new ConfigOptionDTO($this, $options[$this->key] ?? $this->default_value))->render();
    }

    public function validate()
    {
        return (new ConfigOptionDTO($this, null))->validate();
    }

    public function getPricingArray()
    {
        if ($this->type == self::TYPE_RADIO || $this->type == self::TYPE_DROPDOWN) {
            return $this->options->mapWithKeys(function ($option) {
                return [$option->value => PricingService::for($option->id, 'config_options_option')->toArray()];
            })->toArray();
        }

        return PricingService::for($this->id, 'config_option')->toArray();
    }

    public function addOption(string $friendly_name, string $value)
    {
        $option = new ConfigOptionsOption;
        $option->friendly_name = $friendly_name;
        $option->value = $value;
        $option->config_option_id = $this->id;
        $option->sort_order = $this->options->count() + 1;
        $option->save();
        $firstOption = $this->options->first();
        if ($firstOption) {
            /** @var Pricing $pricing */
            $pricing = Pricing::where('related_id', $firstOption->id)->where('related_type', 'config_options_option')->first();
            $pricing->replicate([
                'id',
                'related_id',
                'related_type',
            ])->fill([
                'related_id' => $option->id,
                'related_type' => 'config_options_option',
                'currency' => currency(),
            ])->save();
        } else {
            $pricing = new Pricing;
            $pricing->related_type = 'config_options_option';
            $pricing->related_id = $option->id;
            $pricing->currency = currency();
            $pricing->save();
        }
        return $option;
    }

    public static function getTypes()
    {
        return [
            self::TYPE_TEXT => __('provisioning.admin.configoptions.types.text'),
            self::TYPE_CHECKBOX => __('provisioning.admin.configoptions.types.checkbox'),
            self::TYPE_NUMBER => __('provisioning.admin.configoptions.types.number'),
            self::TYPE_TEXTAREA => __('provisioning.admin.configoptions.types.textarea'),
            self::TYPE_SLIDER => __('provisioning.admin.configoptions.types.slider'),
            self::TYPE_RADIO => __('provisioning.admin.configoptions.types.radio'),
            self::TYPE_DROPDOWN => __('provisioning.admin.configoptions.types.dropdown'),
        ];
    }
}
