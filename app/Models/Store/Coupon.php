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
use App\Models\Billing\Invoice;
use App\Models\Provisioning\Service;
use App\Models\Store\Basket\Basket;
use App\Models\Store\Basket\BasketRow;
use App\Models\Traits\HasMetadata;
use App\Models\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Session;

/**
 * @property int $id
 * @property string $code
 * @property string $type
 * @property int $applied_month
 * @property int $free_setup
 * @property \Illuminate\Support\Carbon|null $start_at
 * @property \Illuminate\Support\Carbon|null $end_at
 * @property int $first_order_only
 * @property int $max_uses
 * @property int $max_uses_per_customer
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Store\CouponUsage> $usages
 * @property int $unique_use
 * @property int|null $customer_id
 * @property array $products_required
 * @property int $is_global
 * @property float $minimum_order_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Customer|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Metadata> $metadata
 * @property-read int|null $metadata_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Store\Pricing> $pricing
 * @property-read int|null $pricing_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Store\Product> $products
 * @property-read int|null $products_count
 * @property-read int|null $usages_count
 *
 * @method static \Database\Factories\CouponFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereAppliedMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereFirstOrderOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereFreeSetup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereIsGlobal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereMaxUses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereMaxUsesPerCustomer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereMinimumOrderAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereProductsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereUniqueUse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereUsages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Coupon extends Model
{
    use HasFactory, HasMetadata, Loggable, SoftDeletes;

    const TYPE_FIXED = 'fixed';

    const TYPE_PERCENT = 'percent';

    const APPLIED_MONTH_UNLIMITED = -1;

    const APPLIED_MONTH_FIRST = 0;

    const UNLIMITED_USE = 0;

    protected $fillable = [
        'code',
        'type',
        'applied_month',
        'free_setup',
        'start_at',
        'end_at',
        'first_order_only',
        'max_uses',
        'max_uses_per_customer',
        'usages',
        'unique_use',
        'customer_id',
        'products_required',
        'minimum_order_amount',
        'is_global',
    ];

    protected $casts = [
        'products_required' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    protected $attributes = [
        'free_setup' => false,
        'first_order_only' => false,
        'is_global' => false,
        'products_required' => '[]',
        'applied_month' => -1,
        'max_uses' => 0,
        'max_uses_per_customer' => 0,
        'usages' => 0,
        'minimum_order_amount' => 0,

    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($coupon) {
            $coupon->usages()->delete();
            $coupon->products()->detach();
            $coupon->pricing()->delete();
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'coupon_products');
    }

    public function pricing()
    {
        return $this->hasMany(Pricing::class, 'related_id')->where('related_type', 'coupon');
    }

    public function isValidForServiceRenewal(Service $service)
    {
        if ($this->applied_month == Coupon::APPLIED_MONTH_FIRST && $service->renewals > 0) {
            return false;
        } elseif ($this->applied_month != Coupon::APPLIED_MONTH_UNLIMITED) {
            if ($service->renewals >= $this->applied_month) {
                return false;
            }
            if ($this->getPricingRecurring($service->billing, BasketRow::PRICE) <= 0) {
                return false;
            }

            return true;
        } else {
            if ($this->getPricingRecurring($service->billing, BasketRow::PRICE) <= 0) {
                return false;
            }

            return true;
        }
    }

    public function isValid(Basket $basket, bool $flash = true)
    {
        if ($this->start_at && $this->start_at->isFuture()) {
            if ($flash) {
                Session::flash('error', __('coupon.coupon_not_started'));
            }

            return false;
        }
        if ($this->end_at && $this->end_at->isPast()) {
            if ($flash) {
                Session::flash('error', __('coupon.coupon_expired'));
            }

            return false;
        }
        if ($this->customer_id && $this->customer_id != $basket->user_id) {
            if ($flash) {
                Session::flash('error', __('coupon.not_found'));
            }

            return false;
        }
        if ($this->first_order_only && $basket->user_id != null && Invoice::where('customer_id', $basket->user_id)->where('status', Invoice::STATUS_PAID)->count() > 0) {
            if ($flash) {
                Session::flash('error', __('coupon.first_order_only'));
            }

            return false;
        }
        if ($this->max_uses > 0 && $this->usages()->count() >= $this->max_uses) {
            if ($flash) {
                Session::flash('error', __('coupon.coupon_max_uses'));
            }

            return false;
        }
        if ($this->max_uses_per_customer > 0 && $this->usages()->where('customer_id', $basket->customer_id)->count() >= $this->max_uses_per_customer) {
            if ($flash) {
                Session::flash('error', __('coupon.coupon_max_use_per_customer'));
            }

            return false;
        }
        if ($this->minimum_order_amount > 0 && $basket->subtotalWithoutCoupon() < $this->minimum_order_amount) {
            if ($flash) {
                Session::flash('error', __('coupon.minimum_order_amount', ['amount' => formatted_price($this->minimum_order_amount)]));
            }

            return false;
        }
        if ($this->products_required && ! $this->is_global) {
            $products = $basket->rows->map(function ($row) {
                return $row->product_id;
            });
            foreach ($this->products_required as $product) {
                if (! $products->contains($product)) {
                    if ($flash) {
                        Session::flash('error', __('coupon.coupon_not_valid_product'));
                    }

                    return false;
                }
            }
        }
        if (! $this->is_global && $this->products()->count() > 0) {
            $basketRowProductIds = $basket->rows->map(function ($row) {
                return $row->product_id;
            });
            $productIds = $this->products->pluck('id');
            if ($productIds->intersect($basketRowProductIds)->count() == 0) {
                if ($flash) {
                    Session::flash('error', __('coupon.coupon_not_valid_product'));
                }

                return false;
            }
        }

        return true;
    }

    public function getPricingRecurring(string $recurring, string $type)
    {
        if (\Cache::get('coupon_'.$this->id) == null) {
            $pricing = $this->pricing()->first();
            if ($pricing == null) {
                throw new \Exception(sprintf('Coupon Pricing %d not found', $this->id));
            }
            \Cache::put('coupon_'.$this->id, $pricing, 60 * 24);
        }
        $pricing = \Cache::get('coupon_'.$this->id);
        if ($type == BasketRow::PRICE) {
            return $pricing->$recurring;
        }
        $recurring = 'setup_'.$recurring;

        return $pricing->$recurring;
    }

    public function applyAmount(float $amount, string $recurring, string $type)
    {
        if ($type == BasketRow::SETUP_FEES && $this->free_setup) {
            return 0;
        }
        $value = $this->getPricingRecurring($recurring, $type);
        if ($this->type == self::TYPE_FIXED) {
            return $amount - $value;
        }
        if ($this->type == self::TYPE_PERCENT) {
            return $amount - ($amount * ($value / 100));
        }
        if ($type == BasketRow::OPTION) {
            // TODO: Implement option discount
            return $amount;
        }
    }

    public function canBeAppliedToBasket(Basket $basket): bool
    {
        $can = false;
        /** @var BasketRow $row */
        foreach ($basket->rows as $row) {
            if ($this->getPricingRecurring($row->billing, BasketRow::PRICE) != 0) {
                $can = true;
            }
            if ($this->getPricingRecurring($row->billing, BasketRow::SETUP_FEES) != 0 || $this->free_setup) {
                $can = true;
            }
        }

        return $can;
    }

    public function discountArray(float $unit_price_ht, float $unit_setup_ht, string $billing)
    {
        return [
            'code' => $this->code,
            'type' => $this->type,
            'id' => $this->id,
            'applied_month' => $this->applied_month,
            'free_setup' => $this->free_setup,
            'sub_price' => number_format($unit_price_ht - $this->applyAmount($unit_price_ht, $billing, BasketRow::PRICE), 2),
            'sub_setup' => number_format($unit_setup_ht - $this->applyAmount($unit_setup_ht, $billing, BasketRow::SETUP_FEES), 2),
            'value_price' => (float) $this->getPricingRecurring($billing, BasketRow::PRICE) ?? 0,
            'value_setup' => (float) $this->getPricingRecurring($billing, BasketRow::SETUP_FEES) ?? 0,
        ];
    }

    protected static function newFactory()
    {
        return \Database\Factories\CouponFactory::new();
    }
}
