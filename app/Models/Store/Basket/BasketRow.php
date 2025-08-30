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


namespace App\Models\Store\Basket;

use App\Casts\OptionCast;
use App\DTO\Store\ProductDataDTO;
use App\Models\Store\Coupon;
use App\Models\Store\Product;
use App\Services\Store\TaxesService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property Product $product
 * @property int $id
 * @property int|null $basket_id
 * @property int|null $product_id
 * @property mixed|null $options
 * @property int $quantity
 * @property array|null $data
 * @property string $billing
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Store\Basket\Basket|null $basket
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow whereBasketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow whereBilling($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BasketRow whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BasketRow extends Model
{
    use BasketRowCouponTrait;
    use BasketRowOptionsTrait;
    use HasFactory;

    const PRICE = 'price';

    const SETUP_FEES = 'setup';

    const OPTION = 'option';

    protected $table = 'baskets_rows';

    protected $fillable = [
        'basket_id',
        'product_id',
        'options',
        'quantity',
        'data',
        'billing',
        'currency',
    ];

    protected $casts = [
        'options' => OptionCast::class,
        'data' => 'array',
        'quantity' => 'integer',
    ];

    protected $attributes = [
        'options' => '[]',
        'data' => '{}',
        'billing' => 'monthly',
        'quantity' => 1,
        'currency' => 'eur',
    ];

    public function applyCoupon(float $price, string $type)
    {
        if ($this->basket == null) {
            return $price;
        }
        /** @var Coupon $coupon */
        $coupon = $this->basket->coupon;
        if ($coupon != null && $this->enableCoupon) {
            if ($this->canApplyCoupon($coupon)) {
                return $coupon->applyAmount($price, $this->billing, $type);
            }
        }

        return $price;
    }

    public function recurringPayment(bool $withQuantity = true)
    {
        if ($this->billing == 'onetime') {
            return 0;
        }
        $this->enableCoupon();
        if (! $withQuantity) {
            $recurringPayment = $this->product->getPriceByCurrency($this->currency, $this->billing)->recurringPayment();
        } else {
            $recurringPayment = $this->product->getPriceByCurrency($this->currency, $this->billing)->recurringPayment() * $this->quantity;
        }

        return $this->applyCoupon($recurringPayment, self::PRICE);
    }

    public function onetimePayment(bool $withQuantity = true)
    {
        if ($this->billing != 'onetime') {
            return 0;
        }
        $this->enableCoupon();
        if (! $withQuantity) {
            $onetimePayment = $this->product->getPriceByCurrency($this->currency, $this->billing)->onetimePayment();
        } else {
            $onetimePayment = $this->product->getPriceByCurrency($this->currency, $this->billing)->onetimePayment() * $this->quantity;
        }

        return $this->applyCoupon($onetimePayment, self::PRICE);
    }

    public function setup(bool $withQuantity = true)
    {
        $this->enableCoupon();
        if (! $withQuantity) {
            $setup = $this->product->getPriceByCurrency($this->currency, $this->billing)->setup();
        } else {
            $setup = $this->product->getPriceByCurrency($this->currency, $this->billing)->setup() * $this->quantity;
        }

        return $this->applyCoupon($setup, self::SETUP_FEES);
    }

    public function amountBillable(): float
    {
        $this->enableCoupon();
        $amount = $this->product->getPriceByCurrency($this->currency, $this->billing)->billableAmount() * $this->quantity;
        $amount += $this->optionsAmountBillable();
        return $this->applyCoupon($amount, self::PRICE);
    }

    /**
     * Renvoie le pourcentage de taxes de la commande
     */
    public function taxPercent(): float
    {
        return tax_percent();
    }

    /**
     * Renvoie le montant des taxes de la commande (subtotal * taxPercent)
     *
     * @return float
     */
    public function tax()
    {
        return TaxesService::getVatPrice($this->amountBillable());
    }

    /**
     * The total with quantity
     *
     * @return float|int
     */
    public function total()
    {
        return $this->subtotal() + $this->tax();
    }

    public function subtotal()
    {
        return $this->recurringPayment() + $this->setup() + $this->onetimePayment() + $this->optionRecurringPayment() + $this->optionSetup() + $this->optionOnetimePayment();
    }

    public function basket()
    {
        return $this->belongsTo(Basket::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public static function findByProductOnSession(Product $product, bool $force = true): ?BasketRow
    {
        $basket = Basket::getBasket();
        if (! $basket) {
            throw new \RuntimeException('No basket found for the current session');
        }

        $row = self::where('basket_id', $basket->id)
            ->where('product_id', $product->id)
            ->first();

        if (! $force) {
            if ($row === null) {
                $row = new BasketRow([
                    'product_id' => $product->id,
                    'basket_id' => $basket->id,
                    'currency' => currency(),
                ]);
            }
        } else {
            if ($row === null) {
                $row = new BasketRow([
                    'product_id' => $product->id,
                    'basket_id' => $basket->id,
                    'currency' => currency(),
                ]);
                $row->save();
            }
        }

        return $row;
    }

    public function primary()
    {
        if ($this->product->productType()->data($this->product) != null) {
            return $this->product->productType()->data($this->product)->primary(new ProductDataDTO($this->product, $this->data ?? [], $this->options ?? [], []));
        }

        return null;
    }

    public function name()
    {
        return $this->product->name;
    }

    public function canApplyCoupon(Coupon $coupon)
    {
        if ($coupon->products_required && ! $coupon->is_global) {
            $products = $this->basket->rows->map(function ($row) {
                return $row->product_id;
            });
            if ($products->intersect($coupon->products->pluck('id'))->count() == 0) {
                return false;
            }
        }
        if ($coupon->products()->count() > 0) {
            if (! $coupon->products->contains($this->product->id)) {
                return false;
            }
        }

        return true;
    }

    public function getDiscountArray()
    {
        if ($this->basket->coupon == null) {
            return [];
        }
        /** @var Coupon $coupon */
        $coupon = $this->basket->coupon;

        return [
            'code' => $coupon->code,
            'type' => $coupon->type,
            'id' => $coupon->id,
            'applied_month' => $coupon->applied_month,
            'free_setup' => $coupon->free_setup,
            'pricing' => $coupon->pricing()->first(),
            'sub_price' => number_format($this->recurringPaymentWithoutCoupon() - $this->recurringPayment() + $this->onetimePaymentWithoutCoupon() - $this->onetimePayment(), 2),
            'sub_setup' => number_format($this->setupWithoutCoupon() - $this->setup(), 2),
            'value_price' => (float) $coupon->getPricingRecurring($this->billing, self::PRICE) ?? 0,
            'value_setup' => (float) $coupon->getPricingRecurring($this->billing, self::SETUP_FEES) ?? 0,
        ];
    }

    public function canChangeQuantity()
    {
        if ($this->product->hasMetadata('disabled_many_services')) {
            return false;
        }
        if ($this->product->productType()->data($this->product) != null) {
            return false;
        }

        return true;
    }
}
