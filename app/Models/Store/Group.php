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

use App\DTO\Store\ProductPriceDTO;
use App\Models\Traits\HasMetadata;
use App\Models\Traits\Loggable;
use App\Models\Traits\ModelStatutTrait;
use App\Models\Traits\Translatable;
use App\Services\Store\CurrencyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema (
 *      schema="ShopGroup",
 *     title="Shop group",
 *     description="Shop group model"
 * )
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $status
 * @property string $description
 * @property string|null $image
 * @property bool $pinned
 * @property int $sort_order
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Group> $groups
 * @property-read int|null $groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Metadata> $metadata
 * @property-read int|null $metadata_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Store\Product> $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Personalization\Translation> $translations
 * @property-read int|null $translations_count
 *
 * @method static \Database\Factories\Store\GroupFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group wherePinned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Group extends Model
{
    use HasFactory, HasMetadata, Loggable, ModelStatutTrait, SoftDeletes, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     *
     * @OA\Property(
     *     property="id",
     *     type="integer",
     *     description="The id of the item",
     *     example="10"
     * ),
     * @OA\Property(
     *     property="name",
     *     type="string",
     *     description="The name of the item",
     *     example="Sample Item"
     * )
     * @OA\Property(
     *     property="slug",
     *     type="string",
     *     description="The URL-friendly slug for the item",
     *     example="sample-item"
     * )
     * @OA\Property(
     *     property="status",
     *     type="string",
     *     description="The status of the item (e.g., active, inactive)",
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
     *     nullable=true,
     *     description="The id of the group to which the item belongs",
     *     example=1
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
     *     description="The URL or path to the item's image",
     *     example="groups/filename.jpg"
     * ),
     * @OA\Property(
     *     property="parent_id",
     *     type="integer",
     *     nullable=true,
     *     description="The id of the parent group",
     *     example=1
     * )
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
        'description',
        'sort_order',
        'pinned',
        'image',
        'parent_id',
    ];

    protected $casts = [
        'pinned' => 'boolean',
    ];

    protected $attributes = [
        'parent_id' => null,
        'status' => 'active',
        'sort_order' => 0,
        'pinned' => false,
    ];

    private array $translatableKeys = [
        'name' => 'text',
        'description' => 'textarea',
        'slug' => 'text',
    ];

    protected $with = ['groups', 'products'];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($group) {
            $group->products()->delete();
            if ($group->image != null) {
                \Storage::delete($group->image);
            }
        });
    }

    public static function parents()
    {
        return self::where('parent_id', null);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class, 'parent_id');
    }

    public function group()
    {
        if ($this->parent_id == null) {
            return null;
        }

        return Group::find($this->parent_id);
    }

    /**
     * Get first price of current group
     */
    public function startPrice(?string $recurring = null, ?string $currency = null): ProductPriceDTO
    {
        if ($recurring == null) {
            $recurring = 'monthly';
        }

        if ($currency == null) {
            $currency = app(CurrencyService::class)->retrieveCurrency();
        }
        $products = $this->products->where('status', 'active')->all();
        foreach ($this->groups as $group) {
            $products = array_merge($products, $group->products->where('status', 'active')->all());
        }
        $prices = [];
        /** @var Product $product */
        foreach ($products as $product) {
            if ($product->isPersonalized()) {
                continue;
            }
            $price = $product->getPriceByCurrency($currency, $recurring);
            if ($price->price == 0) {
                $price = $product->getFirstPrice();
            }
            $prices[] = $price->price;
            $currency = $price->currency;
            $recurring = $price->recurring;
        }
        sort($prices);

        return new ProductPriceDTO($prices[0] ?? 0, 0, $currency, $recurring);
    }

    public function route(bool $absolute = true)
    {

        if ($this->hasMetadata('group_url')) {
            return $this->getMetadata('group_url');
        }
        if ($this->parent_id) {
            return route('front.store.subgroup', [$this->group()->trans('slug', $this->group()->slug), $this->trans('slug', $this->slug)], $absolute);
        }

        return route('front.store.group', $this->trans('slug', $this->slug), $absolute);
    }

    public function isSubgroup()
    {
        return $this->parent_id !== null;
    }

    public function isGroup()
    {
        return $this->parent_id === null;
    }

    public function useImageAsBackground(): bool
    {
        return $this->getMetadata('use_image_as_background') === 'true';
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        $attributes['start_price'] = $this->startPrice();
        $attributes['route'] = $this->route();

        return $attributes;
    }
}
