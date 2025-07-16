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

use App\Addons\Freetrial\DTO\FreetrialDTO;
use App\Addons\Fund\DTO\AddFundDTO;
use App\Addons\Giftcard\Models\Giftcard;
use App\Addons\Giftcard\Notifications\RedeemGiftcardMail;
use App\Casts\JsonToObject;
use App\Contracts\Store\ProductTypeInterface;
use App\DTO\Store\ProductDataDTO;
use App\Models\Provisioning\ConfigOptionService;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceRenewals;
use App\Models\Store\Product;
use App\Models\Traits\HasMetadata;
use App\Services\Billing\InvoiceService;
use Database\Factories\Core\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * 
 *
 * @OA\Schema (
 *     schema="InvoiceItem",
 *     title="Invoice Item",
 *     description="A product or service included in an invoice",
 *     required={"invoice_id", "name", "quantity", "unit_price_ht"},
 * 
 *     @OA\Property(property="id", type="integer", example=3001),
 *     @OA\Property(property="invoice_id", type="integer", example=1001),
 *     @OA\Property(property="name", type="string", example="Web Hosting - Premium"),
 *     @OA\Property(property="description", type="string", example="Includes 10GB of storage and SSL certificate"),
 *     @OA\Property(property="quantity", type="integer", example=1),
 *     @OA\Property(property="unit_price_ht", type="number", format="float", example=50.00),
 *     @OA\Property(property="unit_setup_ht", type="number", format="float", example=10.00),
 *     @OA\Property(property="unit_price_ttc", type="number", format="float", example=72.00),
 *     @OA\Property(property="unit_setup_ttc", type="number", format="float", example=12.00),
 *     @OA\Property(property="type", type="string", example="service"),
 *     @OA\Property(property="related_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="delivered_at", type="string", format="date-time", nullable=true, example="2024-05-10T09:00:00Z"),
 *     @OA\Property(property="cancelled_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="refunded_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="data", type="object", description="Custom data associated with this item"),
 *     @OA\Property(property="discount", type="object", description="Optional discount structure for the item"),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=null)
 * )
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $quantity
 * @property mixed|null $discount
 * @property float $unit_price_ht
 * @property float $unit_setup_ht
 * @property float $unit_price_ttc
 * @property float $unit_setup_ttc
 * @property int $invoice_id
 * @property string $type
 * @property int|null $related_id
 * @property array $data
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $refunded_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $parent_id
 * @property float $unit_original_price
 * @property float $unit_original_setupfees
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InvoiceItem> $childrens
 * @property-read int|null $childrens_count
 * @property-read \App\Models\Billing\Invoice $invoice
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Metadata> $metadata
 * @property-read int|null $metadata_count
 * @property-read InvoiceItem|null $parent
 * @method static \Database\Factories\Core\InvoiceItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereDeliveredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereRefundedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereRelatedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUnitOriginalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUnitOriginalSetupfees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUnitPriceHt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUnitPriceTtc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUnitSetupHt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUnitSetupTtc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem withoutTrashed()
 * @mixin \Eloquent
 */
class InvoiceItem extends Model
{
    use HasFactory;
    use HasMetadata;
    use SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'description',
        'name',
        'quantity',
        'unit_price',
        'discount',
        'unit_price_ttc',
        'unit_price_ht',
        'unit_setup_ht',
        'unit_setup_ttc',
        'type',
        'related_id',
        'delivered_at',
        'cancelled_at',
        'refunded_at',
        'data',
        'parent_id',
    ];

    protected $attributes = [
        'data' => '[]',
        'discount' => '[]',
    ];

    protected $casts = [
        'data' => 'array',
        'discount' => JsonToObject::class,
        'unit_price' => 'float',
        'unit_price_ttc' => 'float',
        'unit_price_ht' => 'float',
        'unit_setup_ttc' => 'float',
        'unit_setup_ht' => 'float',
        'quantity' => 'integer',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'invoice_id' => 'integer',
        'related_id' => 'integer',
        'type' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        static::observe(\App\Observers\InvoiceItemObserver::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function cancel()
    {
        $this->cancelled_at = now();
        $this->save();
    }

    public function refund()
    {
        $this->refunded_at = now();
        $this->save();
    }

    public function price()
    {
        return $this->unit_price_ht * $this->quantity + $this->unit_setup_ht * $this->quantity;
    }

    public function canDisplayDescription()
    {
        if (Str::startsWith($this->description, 'Created from') || Str::startsWith($this->description, 'Add extra')) {
            return false;
        }

        return $this->description != $this->name;
    }

    /**
     * @return Product|Service|\App\Models\Billing\CustomItem|AddFundDTO|FreetrialDTO|Giftcard
     *
     * @throws \Exception
     */
    public function relatedType()
    {
        if (in_array($this->type, ProductTypeInterface::ALL)) {
            return Product::find($this->related_id);
        }
        if ($this->type == 'renewal') {
            return Service::find($this->related_id);
        }
        if ($this->type == CustomItem::CUSTOM_ITEM) {
            return CustomItem::find($this->related_id);
        }
        if ($this->type == 'config_option') {
            return ConfigOption::find($this->related_id);
        }
        if ($this->type == 'config_option_service') {
            return ConfigOptionService::find($this->related_id);
        }
        if ($this->type == 'upgrade') {
            return Upgrade::find($this->related_id);
        }
        if (class_exists(AddFundDTO::class) && $this->type == AddFundDTO::ADD_FUND_TYPE) {
            return new AddFundDTO($this->invoice_id);
        }
        if (class_exists(FreetrialDTO::class) && $this->type == 'free_trial') {
            return new FreetrialDTO($this->related_id, $this);
        }
        if (class_exists(Giftcard::class) && $this->type == Giftcard::GIFT_CARD_TYPE) {
            return Giftcard::find($this->related_id);
        }
        throw new \Exception('InvoiceItem : Unknown type '.$this->type);
    }

    public function billing()
    {
        return $this->data['billing'] ?? 'monthly';
    }

    public function renderHTML(bool $inAdmin = false)
    {
        if ($this->relatedType() instanceof Product) {
            if ($this->relatedType()->productType()->data($this->relatedType()) == null) {
                return '';
            }

            return $this->relatedType()->productType()->data($this->relatedType())->render(new ProductDataDTO($this->relatedType(), $this->data + ['in_admin' => true], [], []));
        }
    }

    protected static function newFactory()
    {
        return InvoiceItemFactory::new();
    }

    public static function findItemsMustDeliver(): Collection
    {
        return self::where('delivered_at', null)
            ->where('cancelled_at', null)
            ->where('refunded_at', null)
            ->select('invoice_items.*')
            ->where('invoices.status', Invoice::STATUS_PAID)
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->get();
    }

    public function hasDiscount()
    {
        if ($this->discount == null) {
            return false;
        }

        return ! empty($this->discount);
    }

    public function getDiscount(bool $force = true)
    {
        $default = new \stdClass;
        $default->value_price = 0;
        $default->value_setup = 0;
        $default->sub_price = 0;
        $default->sub_setup = 0;
        $default->type = 'fixed';
        $default->code = '';
        if (! $this->hasDiscount()) {
            if ($force) {
                return $default;
            }

            return null;
        }
        if (is_object($this->discount)) {
            if (! property_exists($this->discount, 'value_price')) {
                if ($force) {
                    return $default;
                }

                return null;
            }

            return $this->discount;
        }
        $decoded = json_decode($this->discount);
        if ($decoded == null) {
            if ($force) {
                return $default;
            }

            return null;
        }
        if (property_exists($decoded, 'value_price')) {
            return $decoded;
        }
        if ($force) {
            return $default;
        }

        return null;
    }

    public function getDiscountLabel()
    {
        $discount = $this->getDiscount();
        if ($discount === null) {
            return null;
        }
        $code = $discount->code;
        if (! property_exists($discount, 'value_price') || $discount->value_price == 0) {
            return null;
        }
        if ($discount->type == 'fixed') {
            return __('coupon.coupon_label', ['code' => $code, 'discount' => '-'.formatted_price($discount->value_price, $this->invoice->currency)]);
        }

        return __('coupon.coupon_label', ['code' => $code, 'discount' => '-'.$discount->value_price.'%']);
    }

    public function discountTotal()
    {
        $discount = $this->getDiscount();
        if ($discount === null) {
            return 0;
        }

        return $discount->sub_price + $discount->sub_setup;
    }

    public function couponId()
    {
        $discount = $this->getDiscount();
        if ($discount === null) {
            return null;
        }

        return $discount->id ?? null;
    }

    public function tryDeliver()
    {
        if ($this->type == 'add_fund') {
            $this->invoice->customer->addFund($this->unit_price_ht);
            $this->delivered_at = now();
            $this->save();
            return true;
        }
        if ($this->type == 'custom_item') {
            $this->delivered_at = now();
            $this->save();
            return true;
        }
        if ($this->type == 'gift_card') {
            $giftCard = $this->relatedType();
            if ($giftCard == null) {
                throw new \Exception("Gift card not found for invoice item {$this->id}");
            }
            if ($giftCard->customer != null){
                $customer = $giftCard->customer;
            } else {
                $customer = $this->invoice->customer;
            }
            if ($customer == null) {
                throw new \Exception("Customer not found for invoice item {$this->id}");
            }
            if ($giftCard->isValid($customer)) {
                $customer->notify(new RedeemGiftcardMail($giftCard, $this->data['message']));
                $this->delivered_at = now();
                $this->save();
                return true;
            } else {
                throw new \Exception("Gift card {$giftCard->code} is not valid for invoice item {$this->id}");
            }
        }
        if ($this->type == 'renewal') {
            $service = $this->relatedType();
            if ($service == null) {
                throw new \Exception("Service not found for invoice item {$this->id}");
            }
            $service->renew($this->data['billing'] ?? null);
            $this->delivered_at = now();
            $this->save();
            ServiceRenewals::where('invoice_id', $this->invoice_id)->update(['renewed_at' => now()]);

            return true;
        } elseif ($this->type == 'service' || $this->type == 'free_trial') {
            $services = $this->getMetadata('services');
            if ($services == null) {
                try {
                    InvoiceService::createServicesFromInvoiceItem($this->invoice, $this);
                    $services = $this->getMetadata('services');
                } catch (\Exception $e) {
                    throw new \Exception("Error creating services for invoice item {$this->id} : ".$e->getMessage());
                }
            }
            $delivered = [];
            $services = explode(',', $services);
            foreach ($services as $serviceId) {
                $service = Service::find($serviceId);
                if ($service == null) {
                    $services = collect($services)->filter(fn ($id) => $id != $serviceId)->implode(',');
                    if (empty($services)) {
                        $this->detachMetadata('services');
                    } else {
                        $this->attachMetadata('services', $services);
                    }
                    throw new \Exception("Service {$serviceId} not found for invoice item {$this->id}");
                }
                if ($service->status == 'active') {
                    $delivered[] = $service->id;

                    continue;
                }
                $result = $service->deliver();
                if ($result->success) {
                    $delivered[] = $service->id;
                } else {
                    throw new \Exception("Service {$service->id} delivery failed Error : ".$service->delivery_errors);
                }
            }
            if (count($delivered) == count($services)) {
                $this->delivered_at = now();
                $this->save();

                return true;
            }
        } elseif ($this->type == 'config_option') {
            $configOption = $this->relatedType();
            if ($configOption == null) {
                throw new \Exception("Config option not found for invoice item {$this->id}");
            }
            if ($this->parent_id == null) {
                throw new \Exception("Parent id not found for invoice item {$this->id}");
            }
            if ($this->parent->delivered_at != null) {
                if (! $configOption->automatic) {
                    return true;
                }
                $this->delivered_at = now();
                $this->save();
            }

            return true;
        } elseif ($this->type == 'config_option_service') {
            /** @var ConfigOptionService $configOptionService */
            $configOptionService = $this->relatedType();
            if ($configOptionService == null) {
                throw new \Exception("Config option service not found for invoice item {$this->id}");
            }
            if ($this->parent_id == null) {
                throw new \Exception("Parent id not found for invoice item {$this->id}");
            }
            if ($this->parent->delivered_at != null) {
                if (! $configOptionService->option->automatic) {
                    return true;
                }
                $configOptionService->renew($this->data['expires_at']);
                $this->delivered_at = now();
                $this->save();
            }

            return true;
        } elseif ($this->type == 'upgrade') {
            /** @var \App\Models\Billing\Upgrade $upgrade */
            $upgrade = $this->relatedType();
            if ($upgrade == null) {
                throw new \Exception("Upgrade not found for invoice item {$this->id}");
            }
            $result = $upgrade->deliver();
            if ($result->success) {
                $this->delivered_at = now();
                $this->save();
            } else {
                throw new \Exception("Upgrade {$upgrade->id} delivery failed Error : ".$result->message);
            }

            return true;
        }

        return false;
    }

    public function childrens()
    {
        return $this->hasMany(InvoiceItem::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(InvoiceItem::class, 'parent_id');
    }

    public function uncancel()
    {
        $this->cancelled_at = null;
        $this->save();
    }

    public function configoptions()
    {
        return $this->childrens()->where('type', 'config_option')->get();
    }
}
