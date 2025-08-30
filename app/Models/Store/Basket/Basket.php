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

use App\Models\Account\Customer;
use App\Models\Store\Coupon;
use App\Models\Traits\HasMetadata;
use App\Services\Store\TaxesService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * 
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $user_id
 * @property string|null $completed_at
 * @property int|null $coupon_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Coupon|null $coupon
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Metadata> $metadata
 * @property-read int|null $metadata_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Store\Basket\BasketRow> $rows
 * @property-read int|null $rows_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Basket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Basket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Basket query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Basket whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Basket whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Basket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Basket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Basket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Basket whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Basket whereUuid($value)
 * @mixin \Eloquent
 */
class Basket extends Model
{
    use BasketCouponTrait;
    use HasFactory;
    use HasMetadata;

    protected $fillable = [
        'user_id',
        'uuid',
        'coupon_id',
        'completed_at',
        'ip_address',
    ];

    protected $with = ['rows'];

    public static ?Basket $basket = null;

    public function rows()
    {
        return $this->hasMany(BasketRow::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->rows();
    }

    /**
     * Renvoie le pourcentage de taxes de la commande
     *
     * @return float
     */
    public function taxPercent()
    {
        return tax_percent();
    }

    public function recurringPayment(bool $withQuantity = true)
    {
        return $this->rows->reduce(function ($total, BasketRow $row) use ($withQuantity) {
            return $total + $row->recurringPayment($withQuantity) + $row->optionRecurringPayment();
        }, 0);
    }

    public function onetimePayment(bool $withQuantity = true)
    {
        return $this->rows->reduce(function ($total, BasketRow $row) use ($withQuantity) {
            return $total + $row->onetimePayment($withQuantity) + $row->optionOnetimePayment();
        }, 0);
    }

    public function setup(bool $withQuantity = true)
    {
        return $this->rows->reduce(function ($total, BasketRow $row) use ($withQuantity) {
            return $total + $row->setup($withQuantity) + $row->optionSetup();
        }, 0);
    }
    public function amountBillable(): float
    {
        return $this->rows->reduce(function ($total, BasketRow $row) {
            return $total + $row->amountBillable() + $row->optionsAmountBillable();
        }, 0);
    }

    /**
     * Renvoie le montant des taxes de la commande (subtotal * taxPercent)
     *
     * @return float
     */
    public function tax()
    {
        return $this->rows->reduce(function ($total, BasketRow $row) {
            return $total + $row->tax();
        }, 0);
    }

    /**
     * Renvoie-le sous total de la commande (produits + options) sans les taxes
     */
    public function subtotal(): float
    {
        return $this->onetimePayment() + $this->recurringPayment() + $this->setup();
    }

    /**
     * Renvoie la quantité des items de la commande
     *
     * @return int
     */
    public function quantity()
    {
        return (int) $this->rows->sum('quantity');
    }

    /**
     * Renvoie le montant total de la commande (subtotal + taxes)
     */
    public function total(): float
    {
        return $this->subtotal() + $this->tax();
    }

    /**
     * Renvoie la devise de la commande
     *
     * @return string
     */
    public function currency()
    {
        return $this->rows->first()->currency ?? currency();
    }

    /**
     * Permet de vérifier que chaque item de la commande a la même devise
     */
    public function checkCurrency(): bool
    {
        $currency = null;
        $first = $this->rows->first();
        if ($first != null) {
            $currency = $first->currency;
        }
        $filter = $this->rows->filter(function ($row) use ($currency) {
            return $currency != null && $currency != $row->currency;
        });
        $count = $filter->count();
        if ($count > 0) {
            $filter->each(function ($row) {
                $row->delete();
            });

            return false;
        }

        return true;
    }

    /**
     * Permet de vérifier que chaque item de la commande est valide
     */
    public function checkValid(): bool
    {
        $filter = $this->rows->filter(function ($row) {
            if ($row->product == null) {
                return true;
            }
            if ($row->product->stock == -1) {
                return false;
            }

            return $row->product->isNotValid(true) || $row->quantity > $row->product->stock || $row->quantity > 100;
        });
        $count = $filter->count();
        if ($count > 0) {
            $filter->each(function ($row) {
                $row->delete();
            });

            return false;
        }

        return true;
    }

    public static function getBasket(bool $force = true)
    {
        $uuid = self::getUUID();

        if ($force) {
            if (self::$basket != null && !app()->environment('testing')) {
                return self::$basket;
            }

            if (app()->environment('testing')) {
                $basket = self::where('uuid', $uuid)
                    ->whereNull('completed_at')
                    ->first();

                if ($basket) {
                    return $basket;
                }
            }

            $basket = self::firstOrCreate([
                'user_id' => auth('web')->id(),
                'uuid' => $uuid,
                'completed_at' => null,
            ]);

            self::$basket = $basket;
        }

        if (self::$basket == null) {
            self::$basket = self::where('uuid', $uuid)
                ->whereNull('completed_at')
                ->first();
        }

        return self::$basket;
    }

    public function customer()
    {
        if ($this->user_id != null) {
            return $this->belongsTo(Customer::class, 'user_id');
        }

        return null;
    }

    /**
     * Permet de fusionner le panier de l'utilisateur avec le panier de l'invité
     *
     * @return void
     */
    public function mergeBasket(Customer $user)
    {
        $sessionUUID = \session()->get('basket_uuid');
        if ($sessionUUID == null) {
            return;
        }
        $basket = self::where('uuid', $sessionUUID)->first();
        if ($basket) {
            /** @var BasketRow $row */
            foreach ($basket->rows as $row) {
                $row->update(['basket_id' => $this->id]);
            }
            $this->refresh();
            $basket->refresh();
        }
        $this->update(['user_id' => $user->id, 'coupon_id' => $basket ? $basket->coupon_id : null]);
        foreach ($this->rows as $row) {
            if ($row->product == null) {
                $row->delete();
            }
            if (! $row->product->canAddToBasket()) {
                $row->delete();
                Session::flash('error', __('store.basket.already_ordered', ['product' => $row->product->name]));
            }
        }
        if ($this->coupon != null) {
            if (! $this->coupon->isValid($this, true)) {
                $this->update(['coupon_id' => null]);
            }
        }
        $this->refresh();
        if ($basket) {
            $basket->delete();
        }
    }

    public function clear(bool $completed = false)
    {
        $this->rows->each(function ($row) {
            $row->delete();
        });
        if ($completed) {
            $this->update(['completed_at' => now()]);
            if (auth('web')->id() == null) {
                request()->session()->forget('basket_uuid');
            } else {
                auth('web')->user()->attachMetadata('basket_uuid', null);
            }
        }
    }

    public function applyCoupon(string $couponName)
    {
        /** @var Coupon $coupon */
        $coupon = Coupon::where('code', $couponName)->first();
        if ($coupon == null) {
            Session::flash('error', __('coupon.not_found'));

            return false;
        }
        if (! $coupon->isValid($this, true)) {
            return false;
        }
        if (!$coupon->canBeAppliedToBasket($this)) {
            Session::flash('error', __('coupon.coupon_not_applicable'));
            return false;
        }
        $this->update(['coupon_id' => $coupon->id]);

        return true;
    }

    public static function getUUID()
    {
        // If the session is not started, we return a default value for example unit tests
        if (app()->runningUnitTests()) {
            return 'aaaa-aaaa-aaaa-aaaa';
        }
        if (auth('web')->user() != null) {
            $uuid = auth('web')->user()->getMetadata('basket_uuid');
            if ($uuid == null) {
                $uuid = Str::uuid();
                auth('web')->user()->attachMetadata('basket_uuid', $uuid);
            }
        } else {
            $uuid = request()->session()->get('basket_uuid');
            if ($uuid == null) {
                $uuid = Str::uuid();
                request()->session()->put('basket_uuid', $uuid);
            }
        }

        return $uuid;
    }

    public function discount(?string $type = null)
    {
        if ($type == BasketRow::PRICE) {
            $initial = $this->rows->reduce(function ($total, BasketRow $row) {
                return $total + $row->recurringPaymentWithoutCoupon();
            }, 0);
            $discount = $this->rows->reduce(function ($total, BasketRow $row) {
                return $total + $row->recurringPayment();
            }, 0);

            return $initial - $discount;
        } if ($type == BasketRow::SETUP_FEES) {
            $initial = $this->rows->reduce(function ($total, BasketRow $row) {
                return $total + $row->setupWithoutCoupon();
            }, 0);
            $discount = $this->rows->reduce(function ($total, BasketRow $row) {
                return $total + $row->setup();
            }, 0);

            return $initial - $discount;
        }

        return $this->discount(BasketRow::SETUP_FEES) + $this->discount(BasketRow::PRICE);
    }
}
