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

use App\Contracts\Store\ProductTypeInterface;
use App\Core\NoneProductType;
use App\Models\Billing\ConfigOption;
use App\Models\Billing\ConfigOptionsProduct;
use App\Models\Billing\InvoiceItem;
use App\Models\Billing\Traits\PricingInteractTrait;
use App\Models\Provisioning\Service;
use App\Models\Traits\HasMetadata;
use App\Models\Traits\Loggable;
use App\Models\Traits\ModelStatutTrait;
use App\Models\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @OA\Schema (
 *      schema="ShopProduct",
 *     title="Shop product",
 *     description="Shop product model"
 * )
 * @property int $id
 * @property string $name
 * @property int $group_id
 * @property string $status
 * @property string $description
 * @property bool $pinned
 * @property int $stock
 * @property string $type
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $image
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ConfigOption> $configoptions
 * @property-read int|null $configoptions_count
 * @property-read \App\Models\Store\Group $group
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Metadata> $metadata
 * @property-read int|null $metadata_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Store\Pricing> $pricing
 * @property-read int|null $pricing_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Personalization\Translation> $translations
 * @property-read int|null $translations_count
 * @method static \Database\Factories\Store\ProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product wherePinned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 * @mixin \Eloquent
 */
class Product extends Model
{
    protected string $pricing_key = 'product';

    use HasFactory;
    use HasMetadata;
    use Loggable;
    use ModelStatutTrait;
    use PricingInteractTrait;
    use SoftDeletes;
    use Translatable;

    const UNLIMITED_STOCK = -1;

    /**
     * @var string[]
     *
     * @OA\Property(
     *     property="id",
     *     type="integer",
     *     description="The id of the item",
     *     example="10"
     * ),
     *  * @OA\Property(
     *     property="name",
     *     type="string",
     *     description="The name of the item",
     *     example="Sample Item"
     * )
     * @OA\Property(
     *     property="status",
     *     type="string",
     *     description="The status of the item (e.g., Active, Hidden, Unreferenced)",
     *     example="active"
     * )
     * @OA\Property(
     *     property="description",
     *     type="string",
     *     description="A description or details about the item",
     *     example="This is a sample item description."
     * )
     * @OA\Property(
     *     property="sort_order",
     *     type="integer",
     *     description="The order in which the item should be sorted",
     *     example=1
     * )
     * @OA\Property(
     *     property="group_id",
     *     type="integer",
     *     description="The ID of the group to which the item belongs",
     *     example=123
     * )
     * @OA\Property(
     *     property="stock",
     *     type="integer",
     *     description="The stock quantity of the item",
     *     example=50
     * )
     * @OA\Property(
     *     property="type",
     *     type="string",
     *     description="The type of the product",
     *     example="pterodactyl"
     * )
     * @OA\Property(
     *     property="pinned",
     *     type="boolean",
     *     description="Whether the item is pinned or not",
     *     example=true
     * )
     * @OA\Property(
     *     property="image",
     *     type="string",
     *     description="The image URL of the item",
     *     example="storage/products/sample.jpg"
     * )
     */
    protected $fillable = [
        'name',
        'status',
        'description',
        'sort_order',
        'group_id',
        'stock',
        'type',
        'pinned',
        'image',
    ];

    protected $casts = [
        'pinned' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'active',
        'stock' => 10,
        'sort_order' => 0,
    ];

    private array $translatableKeys = [
        'name' => 'text',
        'description' => 'editor',
    ];

    protected $with = ['metadata'];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($product) {
            $product->pricing()->delete();
            if ($product->image != null) {
                \Storage::delete($product->image);
            }
            InvoiceItem::where('related_id', $product->id)
                ->where('type', 'product')
                ->whereNull('delivered_at')
                ->update(['cancelled_at' => now()]);
        });
    }

    public static function getAllProducts(bool $inAdmin = false)
    {
        return self::getAvailable($inAdmin)->pluck('name', 'id')->mapWithKeys(function ($name, $id) {
            return [$id => $name];
        });
    }

    public static function addStock(?int $id = null)
    {
        if ($id == null) {
            return;
        }
        $product = self::find($id);
        if ($product == null) {
            return;
        }

        if ($product->getMetadata('auto_stock') != null) {
            $product->stock += 1;
            $product->save();
        }
        if ($product->stock == self::UNLIMITED_STOCK) {
            return;
        }
    }

    public function isOutOfStock(): bool
    {
        if ($this->stock == self::UNLIMITED_STOCK) {
            return false;
        }

        return $this->stock == 0;
    }

    public static function removeStock(?int $id = null)
    {
        if ($id == null) {
            return;
        }
        $product = self::find($id);
        if ($product == null) {
            return;
        }
        if ($product->getMetadata('auto_stock') != null || $product->stock > 0) {
            $product->stock -= 1;
            $product->save();
        }
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function configoptions()
    {
        return $this->hasManyThrough(
            ConfigOption::class,
            ConfigOptionsProduct::class,
            'product_id',
            'id',
            'id',
            'config_option_id'
        )->where('hidden', false)->orderBy('sort_order');
    }

    public function pricing()
    {
        return $this->hasMany(Pricing::class, 'related_id')->where('related_type', 'product');
    }

    public function basket_url()
    {
        if ($this->hasMetadata('basket_url')) {
            return $this->getMetadata('basket_url');
        }
        if ($this->hasMetadata('is_personalized_product')) {
            if ($this->hasMetadata('personalized_product_url')) {
                return $this->getMetadata('personalized_product_url');
            }

            return route('front.support.create');
        }

        return route('front.store.basket.add', $this->id);
    }

    public function data_url()
    {
        return route('front.store.basket.config', $this->id);
    }

    public function basket_title()
    {
        if ($this->hasMetadata('basket_title')) {
            return __($this->getMetadata('basket_title'));
        }
        if ($this->hasMetadata('is_personalized_product')) {
            return trans('store.basket.contactus');
        }

        return trans('store.basket.addtocart');
    }

    public function isPersonalized(): bool
    {
        return $this->hasMetadata('is_personalized_product');
    }

    public function productType(): ProductTypeInterface
    {
        return app('extension')->getProductTypes()->get($this->type, new NoneProductType);
    }

    public function canAddToBasket(): bool
    {
        $option = $this->getMetadata('allow_only_as_much_services');
        if ($option != null) {
            if (! str_contains($option, ':')) {
                $option .= ':1';
            }
            [$type, $number] = explode(':', $option);
            if ($type == 'active') {
                $service = Service::where('product_id', $this->id)->where('customer_id', auth('web')->id())->where('status', Service::STATUS_ACTIVE)->count();
                if ($service >= $number) {
                    return false;
                }
            }
            if ($type == 'all') {
                $service = Service::where('product_id', $this->id)->where('customer_id', auth('web')->id())->count();
                if ($service >= $number) {
                    return false;
                }
            }
        }
        if ($this->getMetadata('personalized_product_url') != null) {

        }

        return true;
    }

    public function getUpgradeProducts()
    {
        $group = $this->group;
        if ($group == null) {
            return collect();
        }
        if ($group->hasMetadata('disable_upgrade')) {
            return collect();
        }

        return $group->products()->where('status', 'active')->whereNot('id', $this->id)->where('sort_order', '>=', $this->sort_order)->get();
    }

    public function getMetadataLines(): array
    {
        $lines = explode('[--]', $this->getMetadata('product_description'));
        $lines = array_merge($lines, explode('[--]', $this->group->getMetadata('group_description') ?? ''));
        $lines = array_map(function ($line) {
            return trim($line);
        }, $lines);

        return array_filter($lines);
    }

    public function isNotValid(bool $canUnreferenced = false)
    {
        if ($this->getMetadata('is_personalized_product') == 'true') {
            return true;
        }
        // Si il y n'a pas de stock et que le stock n'est pas désactivé ou rend le produit non valide
        if ($this->stock == 0 && $this->getMetadata('disabled_stock') == null) {
            return true;
        }

        return ! $this->isValid($canUnreferenced);
    }
}
