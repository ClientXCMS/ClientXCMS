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

use App\Abstracts\SupportRelateItemTrait;
use App\Contracts\Notifications\HasNotifiableVariablesInterface;
use App\Core\NoneProductType;
use App\DTO\Store\ConfigOptionDTO;
use App\DTO\Store\ProductPriceDTO;
use App\Mail\Service\NotifyExpirationEmail;
use App\Models\Account\Customer;
use App\Models\Billing\ConfigOption;
use App\Models\Billing\Traits\PricingInteractTrait;
use App\Models\Billing\Upgrade;
use App\Models\Store\Basket\BasketRow;
use App\Models\Store\Coupon;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use App\Models\Traits\HasMetadata;
use App\Models\Traits\Loggable;
use App\Services\Store\PricingService;
use App\Services\Store\RecurringService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * 
 *
 * @OA\Schema (
 *      schema="ProvisioningService",
 *     title="Service",
 *     description="service model"
 * )
 * @property int $id
 * @property int $customer_id
 * @property string $uuid
 * @property string $name
 * @property string $type
 * @property string $billing
 * @property int $server_id
 * @property int $product_id
 * @property int $invoice_id
 * @property string $status
 * @property Carbon $expires_at
 * @property Carbon $suspended_at
 * @property Carbon $cancelled_at
 * @property string $cancelled_reason
 * @property string $notes
 * @property string $delivery_errors
 * @property int $delivery_attempts
 * @property int $renewals
 * @property Carbon $trial_ends_at
 * @property int $max_renewals
 * @property array $data
 * @property string $currency
 * @property string $suspend_reason
 * @property bool $is_cancelled
 * @property string $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \App\Models\Billing\Invoice $invoice
 * @property-read \App\Models\Account\Customer $customer
 * @property-read \App\Models\Provisioning\Server $server
 * @property-read \App\Models\Store\Product $product
 * @property-read \App\Models\Billing\Subscription $subscription
 * @property-read \App\Models\Provisioning\ServiceRenewals $serviceRenewals
 * @property-read \App\Models\Provisioning\ConfigOptionService $configoptions
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $configoptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Metadata> $metadata
 * @property-read int|null $metadata_count
 * @property-read int|null $service_renewals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Upgrade> $upgrades
 * @property-read int|null $upgrades_count
 * @method static \Database\Factories\Provisioning\ServiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereBilling($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereCancelledReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereDeliveryAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereDeliveryErrors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereIsCancelled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereMaxRenewals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereRenewals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereSuspendReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereSuspendedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service withoutTrashed()
 * @mixin \Eloquent
 */
class Service extends Model implements HasNotifiableVariablesInterface
{
    use HasFactory, HasMetadata, Loggable, PricingInteractTrait, SoftDeletes, SupportRelateItemTrait, Traits\ServerTypeTrait;

    const STATUS_ACTIVE = 'active';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_PENDING = 'pending';

    const STATUS_EXPIRED = 'expired';

    const STATUS_HIDDEN = 'hidden';

    const FILTERS = [
        'all' => 'all',
        self::STATUS_ACTIVE => 'active',
        self::STATUS_SUSPENDED => 'suspended',
        self::STATUS_CANCELLED => 'cancelled',
        self::STATUS_PENDING => 'pending',
        self::STATUS_EXPIRED => 'expired',
        self::STATUS_HIDDEN => 'hidden',
    ];

    protected string $pricing_key = 'service';

    /**
     * @var string[]
     *
     * @OA\Property(
     *     property="customer_id",
     *     type="integer",
     *     description="The ID of the associated customer",
     *     example=123
     *     ),
     * @OA\Property(
     *     property="uuid",
     *     type="string",
     *     description="The UUID of the service",
     *     example="550e8400-e29b-41d4-a716-446655440000"
     *  ),
     * @OA\Property(
     *     property="name",
     *     type="string",
     *     description="The name of the service",
     *     example="Service name"
     *    ),
     * @OA\Property(
     *     property="type",
     *     type="string",
     *     description="The type of the service",
     *     example="proxmox"
     *   ),
     * @OA\Property(
     *     property="billing",
     *     type="string",
     *     description="The billing of the service",
     *     example="monthly"
     *  ),
     * @OA\Property(
     *     property="server_id",
     *     type="integer",
     *     description="The ID of the associated server",
     *     example=1
     *     ),
     * @OA\Property(
     *     property="product_id",
     *     type="integer",
     *     description="The ID of the associated product (nullable)",
     *     example=1
     *     ),
     * @OA\Property(
     *     property="invoice_id",
     *     type="integer",
     *     description="The ID of the associated invoice for renewal (nullable)",
     *     example=123
     *     ),
     * @OA\Property(
     *     property="status",
     *     type="string",
     *     description="The status of the service",
     *     example="active"
     *    ),
     *  @OA\Property(
     *     property="expires_at",
     *     type="string",
     *     format="date-time",
     *     description="The expiration date of the service",
     *     example="2021-01-01 00:00:00"
     *   ),
     * @OA\Property(
     *     property="suspended_at",
     *     type="string",
     *     format="date-time",
     *     description="The suspension date of the service",
     *     example="2021-01-01 00:00:00"
     *  ),
     * @OA\Property(
     *     property="cancelled_at",
     *     type="string",
     *     format="date-time",
     *     description="The cancellation date of the service",
     *     example="2021-01-01 00:00:00"
     * ),
     * @OA\Property(
     *     property="cancelled_reason",
     *     type="string",
     *     description="The cancellation reason of the service",
     *     example="Service cancelled"
     * ),
     * @OA\Property(
     *     property="notes",
     *      type="string",
     *     description="The notes of the service",
     *     example="Service notes"
     * ),
     * @OA\Property(
     *     property="delivery_errors",
     *     type="STRING",
     *     description="The delivery errors of the service",
     *     example="Delivery errors"
     * ),
     * @OA\Property(
     *     property="delivery_attempts",
     *     type="integer",
     *     description="The delivery attempts of the service",
     *     example=1
     *     ),
     * @OA\Property(
     *     property="renewals",
     *     type="integer",
     *     description="The renewals of the service",
     *     example=1
     *     ),
     * @OA\Property(
     *     property="trial_ends_at",
     *     type="string",
     *     format="date-time",
     *     description="The trial end date of the service",
     *     example="2021-01-01 00:00:00"
     * ),
     * @OA\Property(
     *     property="max_renewals",
     *     type="integer",
     *     description="The maximum renewals of the service",
     *     example=1
     *     ),
     * @OA\Property(
     *     property="data",
     *     type="json",
     *     description="The data of the service",
     *     example={"key":"value"}
     *     ),
     * @OA\Property(
     *     property="currency",
     *     type="string",
     *     description="The currency of the service",
     *      example="USD"
     * ),
     * @OA\Property(
     *     property="suspend_reason",
     *     type="string",
     *     description="The suspension reason of the service",
     *     example="Service suspended"
     * ),
     */
    protected $fillable = [
        'id',
        'customer_id',
        'uuid',
        'name',
        'type',
        'billing',
        'server_id',
        'product_id',
        'invoice_id',
        'status',
        'expires_at',
        'suspended_at',
        'cancelled_at',
        'cancelled_reason',
        'notes',
        'delivery_errors',
        'delivery_attempts',
        'renewals',
        'trial_ends_at',
        'max_renewals',
        'data',
        'currency',
        'suspend_reason',
        'is_cancelled',
        'description',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'suspended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'data' => 'array',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'renewals' => 0,
        'delivery_attempts' => 0,
        'max_renewals' => null,
        'billing' => 'monthly',
        'currency' => 'EUR',
    ];

    public ?Carbon $last_expires_at = null;

    public static function boot()
    {
        parent::boot();
        self::observe(\App\Observers\ServiceObserver::class);
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => __('global.states.active'),
            self::STATUS_SUSPENDED => __('global.states.suspended'),
            self::STATUS_CANCELLED => __('global.states.cancelled'),
            self::STATUS_PENDING => __('global.states.pending'),
            self::STATUS_EXPIRED => __('global.states.expired'),
            self::STATUS_HIDDEN => __('global.states.hidden'),
        ];
    }

    public static function countCustomers(bool $active = false)
    {
        if ($active) {
            return self::where('status', self::STATUS_ACTIVE)->select('customer_id')->get()->unique('customer_id')->count();
        }

        return self::select('customer_id')->get()->unique('customer_id')->count();
    }

    public static function getShouldCreateInvoice()
    {
        return self::where('status', self::STATUS_ACTIVE)
            ->whereNull('invoice_id')
            ->whereNull('trial_ends_at')
            ->whereNull('cancelled_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(setting('days_before_creation_renewal_invoice')))
            ->get();
    }

    public static function getShouldExpire()
    {
        return self::where('status', self::STATUS_SUSPENDED)
            ->whereNotNull('expires_at')
            ->whereRaw('NOW() >= DATE_ADD(expires_at, INTERVAL ? DAY)', [setting('days_before_expiration')])
            ->get();
    }

    public static function getSubscriptionCanBeRenew()
    {
        return Service::where('status', 'active')
            ->select('services.*')
            ->leftJoin('subscriptions', 'services.id', '=', 'subscriptions.service_id')
            ->whereNotNull('subscriptions.id')
            ->whereNull('subscriptions.cancelled_at')
            ->whereNotNull('invoice_id')
            ->where('subscriptions.state', 'active')
            ->whereRaw('subscriptions.billing_day = DAY(CURDATE())')
            ->get();
    }

    public static function getShouldSuspend()
    {
        return self::whereIn('status', [self::STATUS_ACTIVE, self::STATUS_CANCELLED])
            ->where(function ($query) {
                $query->whereNull('cancelled_at')
                    ->orWhere('cancelled_at', '<=', now());
            })
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

    }

    public static function getShouldCancel()
    {
        return self::whereNotNull('cancelled_at')
            ->whereNotNull('cancelled_reason')
            ->where('is_cancelled', false)
            ->whereRaw('NOW() >= cancelled_at')
            ->get();
    }

    public static function getShouldHidden()
    {
        return self::where('status', self::STATUS_EXPIRED)
            ->whereRaw('NOW() >= DATE_ADD(expires_at, INTERVAL 15 DAY)')
            ->get();
    }

    public static function getShouldNotifyExpiration(array $days)
    {
        return self::where('status', self::STATUS_ACTIVE)
            ->whereNull('cancelled_at')
            ->whereNotNull('expires_at')
            ->where(function ($query) use ($days) {
                foreach ($days as $day) {
                    $query->orWhereRaw('DATEDIFF(expires_at, NOW()) = ?', [$day]);
                }
            })->get();
    }

    public function getBillingPrice(?string $billing = null): ProductPriceDTO
    {
        if ($billing == null) {
            $billing = $this->billing;
        }
        if ($this->product_id == null) {
            $pricing = $this->getPriceByCurrency($this->currency, $billing);
        } else {
            $pricing = PricingService::for($this->id, 'service')->first();
            if ($pricing) {
                return new ProductPriceDTO(
                    $pricing[$billing] ?? 0,
                    $pricing['setup_' . $billing] ?? 0,
                    $this->currency,
                    $billing
                );
            }
            $pricing = $this->product->getPriceByCurrency($this->currency, $billing);
        }

        return $pricing;
    }
    public function getPricing(): Pricing
    {
        if ($this->id == null) {
            if ($this->product_id != null) {
                $pricing = PricingService::for($this->product_id, 'product')->first();
                if ($pricing)
                    return new Pricing($pricing);
            }

            return new Pricing(['related_type' => $this->pricing_key, 'currency' => $this->currency]);
        } else {
            $pricing = PricingService::for($this->id, 'service')->first();
            if ($pricing != null) {
                return new Pricing($pricing);
            }
            if ($this->product_id != null) {
                $pricing = PricingService::for($this->product_id, 'product')->first();
                if ($pricing)
                    return new Pricing($pricing);
            }

            return new Pricing(['related_type' => $this->pricing_key, 'related_id' => $this->id, 'currency' => $this->currency]);
        }
    }

    public function allowedBillingsLabels()
    {
        return collect($this->pricingAvailable())->mapWithKeys(function (ProductPriceDTO $price) {
            return [$price->recurring => $price->recurring()['translate']];
        });
    }

    private function getAllPricing(int $related_id)
    {
        $pricing = PricingService::for($related_id, $this->pricing_key);
        if ($pricing->isNotEmpty()) {
            return $pricing;
        }
        if ($this->product_id != null) {
            return $this->product->getAllPricing($this->product_id);
        }
        throw new \Exception('Service Pricing not found for #'.$this->id);
    }

    private function getAllPricingCurrency(int $related_id, string $currency)
    {
        $pricing = PricingService::forCurrency($related_id, $this->pricing_key, $currency);
        if ($pricing != null) {
            return $pricing;
        }
        if ($this->product_id != null) {
            return $this->product->getAllPricingCurrency($related_id, $this->pricing_key, $currency);
        }
        throw new \Exception('Service Pricing not found for #'.$this->id);
    }

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Billing\Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function subscription()
    {
        return $this->hasOne(\App\Models\Billing\Subscription::class)->withTrashed();
    }

    public function getSubscription()
    {
        return $this->subscription()->first() ?? new \App\Models\Billing\Subscription;
    }

    public function canSubscribe()
    {
        if ($this->billing == 'onetime') {
            return false;
        }
        if ($this->expires_at == null) {
            return false;
        }

        return true;
    }

    public function excerptName(int $length = 24)
    {
        return \Str::limit($this->name, $length);
    }

    public function serviceRenewals()
    {
        return $this->hasMany(ServiceRenewals::class);
    }

    public function configoptions()
    {
        return $this->hasMany(ConfigOptionService::class);
    }

    /**
     * @return Pricing[]
     */
    public function getConfigOptionsPrices(): array
    {
        $configOptions = $this->configoptions->where('expired_at', '>', now());
        $prices = [];
        /** @var ConfigOption $configOption */
        foreach ($configOptions as $configOption) {
            $dto = new ConfigOptionDTO($configOption->option, $configOption->value, $configOption->expired_at);
            if ($dto->needRenewal($this)) {
                $prices[] = $configOption->getPricing();
            }
        }

        return $prices;
    }

    public function productType()
    {
        return app('extension')->getProductTypes()->get($this->type, new NoneProductType);
    }

    public function recurring()
    {
        return app(RecurringService::class)->get($this->billing);
    }

    public function upgrades()
    {
        return $this->hasMany(Upgrade::class);
    }

    public function canRenew()
    {
        if ($this->expires_at == null) {
            return false;
        }
        if ($this->billing == 'free' || $this->billing == 'onetime') {
            return false;
        }
        if ($this->getMetadata('free_trial_type', null) == 'simple') {
            return false;
        }
        if (! is_null($this->max_renewals)) {
            return $this->renewals < $this->max_renewals;
        }
        if (in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_SUSPENDED])) {
            return true;
        }

        return false;
    }

    public function isFree()
    {
        return $this->getBillingPrice()->price == 0;
    }

    public function canManage()
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_CANCELLED]);
    }

    public function isActivated()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    public function isSuspended()
    {
        return $this->status == self::STATUS_SUSPENDED;
    }

    public function isCancelled()
    {
        return $this->status == self::STATUS_CANCELLED;
    }

    public function isPending()
    {
        return $this->status == self::STATUS_PENDING;
    }

    public function isExpired()
    {
        return $this->status == self::STATUS_EXPIRED;
    }

    public function isOneTime()
    {
        return $this->billing == 'onetime';
    }

    public function getInvoiceName()
    {
        if ($this->product == null) {
            return $this->name;
        }
        if ($this->billing == 'onetime') {
            return $this->name;
        }
        $current = $this->expires_at->format('d/m/y');
        $expiresAt = app(RecurringService::class)->addFrom($this->expires_at, $this->billing);

        return "{$this->product->trans('name')} ({$current} - {$expiresAt->format('d/m/y')})";
    }

    public function getBillableAmount(string $billing, bool $setup = false): float
    {
        $price = $this->getBillingPrice($billing)->base_price;
        if ($setup) {
            $price += $this->getBillingPrice($billing)->base_setup;
        }
        /** @var ProductPriceDTO $configOptionPrice */
        foreach ($this->getConfigOptionsPrices() as $configOptionPrice) {
            $price += $configOptionPrice->base_price;
            if ($setup) {
                $price += $configOptionPrice->base_setup;
            }
        }
        return $price;
    }

    public function discountArray()
    {
        if (!$this->couponId())
            return null;
        /** @var Coupon $coupon */
        $coupon = Coupon::find($this->couponId());
        if ($coupon == null || ! $coupon->isValidForServiceRenewal($this)) {
            return null;
        }
        return $coupon->discountArray($this->getBillingPrice()->price_ht, 0, $this->billing);
    }

    public function discountAmount()
    {
        if (!$this->couponId()) {
            return 0;
        }
        /** @var Coupon $coupon */
        $coupon = Coupon::find($this->couponId());
        if ($coupon == null || ! $coupon->isValidForServiceRenewal($this)) {
            return 0;
        }
        return $coupon->applyAmount($this->getBillingPrice()->price_ht, $this->billing, BasketRow::PRICE);
    }

    public function relatedName(): string
    {
        return __('global.service').' #'.Str::limit($this->uuid, 5).' - '.$this->excerptName().' - '.$this->status.' - '.($this->expires_at ? $this->expires_at->format('d/m/y') : 'None');
    }

    public function notifyExpiration(): bool
    {
        if (!$this->expires_at){
            return false;
        }
        $remaining = abs((int)\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->expires_at)->diffInDays());
        if ($remaining <= 0) {
            return false;
        }
        if ($remaining == 7 && $this->billing == 'weekly') {
            return false;
        }
        if ($this->getMetadata('disable_notify_expiration')) {
            return false;
        }
        $this->customer->notify(new NotifyExpirationEmail($this, $remaining));

        return true;
    }

    public function canUpgrade()
    {
        if ($this->product_id == null) {
            return false;
        }

        return $this->product->getUpgradeProducts()->count() != 0;
    }

    public function getOptionValue(string $key, $default = null)
    {
        $configOption = $this->configoptions->where('key', $key)->first();
        if ($configOption == null) {
            return $default;
        }

        return $configOption->value;
    }

    public function saveOptions(array $options)
    {
        $configoptions = $this->getConfigOptionsAvailable();
        $keys = $configoptions->map(function ($configoption) {
            return $configoption->key;
        })->toArray();
        $saved = [];
        $configoptionsdto = [];
        $description = $this->description;
        foreach ($options as $key => $value) {
            if ($value == null || ! in_array($key, $keys) || $value == '' || $value == '0') {
                unset($saved[$key]);
            } else {
                $saved[$key] = $value;
                $configoptionsdto[] = new ConfigOptionDTO($configoptions->where('key', $key)->first(), $value, $this->expires_at);
            }
        }
        /** @var ConfigOptionDTO $_item */
        foreach ($configoptionsdto as $_item) {
            $created = $this->configoptions()->create([
                'config_option_id' => $_item->option->id,
                'key' => $_item->option->key,
                'value' => $_item->value,
                'quantity' => $_item->quantity(),
                'expires_at' => $_item->getExpiresAt($this->currency, $this->billing),
            ]);
            $pricing = $_item->option->getPricing();
            if ($pricing != null) {
                $pricing->replicate([
                    'related_id' => $created->id,
                    'related_type' => 'config_options_service',
                ])->save();
                PricingService::forgot();
            }

            $description .= $_item->getBillingDescription().' | ';
        }
        $this->description = $description;
        $this->save();
    }

    public function getConfigOptionsAvailable()
    {
        if ($this->product_id == null) {
            return collect([]);
        }

        return $this->product->configOptions()->get();
    }

    public static function getNotificationContextVariables(): array
    {
        return [
            '%service_name%', '%service_status%', '%service_expires_at%', '%service_price%', '%service_billing%', '%service_type%', '%service_server%', '%service_product%',
        ];
    }

    public function getNotificationVariables(): array
    {
        return [
            '%service_name%' => $this->name,
            '%service_status%' => $this->status,
            '%service_expires_at%' => $this->expires_at ? $this->expires_at->format('d/m/Y') : 'None',
            '%service_price%' => $this->price,
            '%service_billing%' => $this->billing,
            '%service_type%' => $this->type,
            '%service_server%' => $this->server ? $this->server->name : 'None',
            '%service_product%' => $this->product ? $this->product->name : 'None',
        ];
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $model = $this->where('uuid', $value)->first();
        if (! $model) {
            $model = $this->where('id', $value)->first();
        }
        return $model ?? abort(404);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    private function couponId()
    {
        if ($this->hasMetadata('coupon_id')) {
            return $this->getMetadata('coupon_id');
        }
        if ($this->hasMetadata('discount')) {
            $discount = json_decode($this->getMetadata('discount'));
            if (property_exists($discount, 'id')) {
                return $discount->id;
            }
        }
        return null;
    }
}
