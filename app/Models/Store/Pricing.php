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

use App\Services\Store\PricingService;
use App\Services\Store\RecurringService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @OA\Schema (
 *      schema="ShopPricing",
 *     title="Shop pricing",
 *     description="Shop pricing model"
 * )
 * @property int $id
 * @property int $related_id
 * @property string $related_type
 * @property string $currency
 * @property float|null $weekly
 * @property float|null $setup_weekly
 * @property float|null $onetime
 * @property float|null $monthly
 * @property float|null $quarterly
 * @property float|null $semiannually
 * @property float|null $annually
 * @property float|null $biennially
 * @property float|null $triennially
 * @property float|null $setup_onetime
 * @property float|null $setup_monthly
 * @property float|null $setup_quarterly
 * @property float|null $setup_semiannually
 * @property float|null $setup_annually
 * @property float|null $setup_biennially
 * @property float|null $setup_triennially
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Store\Product|null $product
 * @method static \Database\Factories\Store\PricingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereAnnually($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereBiennially($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereMonthly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereOnetime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereQuarterly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereRelatedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereRelatedType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereSemiannually($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereSetupAnnually($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereSetupBiennially($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereSetupMonthly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereSetupOnetime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereSetupQuarterly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereSetupSemiannually($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereSetupTriennially($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereSetupWeekly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereTriennially($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing whereWeekly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pricing withoutTrashed()
 * @mixin \Eloquent
 */
class Pricing extends Model
{
    use HasFactory, softDeletes;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     *
     * @OA\Property(
     *     property="related_id",
     *     type="integer",
     *     description="The ID of the associated item",
     *     example=123
     * ),
     * @OA\Property(
     *     property="related_type",
     *     type="string",
     *     description="The type of the associated item (e.g., product, service, etc.)",
     *     example="product"
     * ),
     * @OA\Property(
     *     property="currency",
     *     type="string",
     *     description="The currency for pricing",
     *     example="USD"
     * ),
     * @OA\Property(
     *     property="onetime",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="One-time payment amount",
     *     example=99.99
     * ),
     * @OA\Property(
     *     property="monthly",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Monthly payment amount",
     *     example=9.99
     * ),
     * @OA\Property(
     *     property="quarterly",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Quarterly payment amount",
     *     example=24.99
     * ),
     * @OA\Property(
     *     property="semiannually",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Semi-annual payment amount",
     *     example=49.99
     * ),
     * @OA\Property(
     *     property="annually",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Annual payment amount",
     *     example=99.99
     * ),
     * @OA\Property(
     *     property="biennially",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Biennial payment amount",
     *     example=199.99
     * ),
     * @OA\Property(
     *     property="triennially",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Triennial payment amount",
     *     example=299.99
     * ),
     * @OA\Property(
     *     property="setup_onetime",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="One-time setup fee amount",
     *     example=19.99
     * ),
     * @OA\Property(
     *     property="setup_monthly",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Monthly setup fee amount",
     *     example=4.99
     * ),
     * @OA\Property(
     *     property="setup_quarterly",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Quarterly setup fee amount",
     *     example=9.99
     * ),
     * @OA\Property(
     *     property="setup_semiannually",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Semi-annual setup fee amount",
     *     example=14.99
     * ),
     * @OA\Property(
     *     property="setup_annually",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Annual setup fee amount",
     *     example=29.99
     * ),
     * @OA\Property(
     *     property="setup_biennially",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Biennial setup fee amount",
     *     example=49.99
     * ),
     * @OA\Property(
     *     property="setup_triennially",
     *     type="number",
     *     format="float",
     *     nullable=true,
     *     description="Triennial setup fee amount",
     *     example=69.99
     * )
     */
    protected $fillable = [
        'related_id',
        'related_type',
        'currency',
        'onetime',
        'monthly',
        'quarterly',
        'semiannually',
        'annually',
        'biennially',
        'triennially',
        'weekly',
        'setup_onetime',
        'setup_monthly',
        'setup_quarterly',
        'setup_semiannually',
        'setup_annually',
        'setup_biennially',
        'setup_triennially',
        'setup_weekly',
    ];

    const ALLOWED_TYPES = ['product', 'service', 'coupon', 'configoption_service', 'config_option', 'option'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'related_id')->where('related_type', 'product');
    }

    public static function getRecurringTypes(): array
    {
        return app(RecurringService::class)->getRecurringTypes();
    }

    public function getFirstRecurringType(): ?string
    {
        return collect($this->getAttributes())->filter(function ($value, $key) {
            // check product id pour les anciennes versions
            $keys = ['id', 'related_id', 'currency', 'related_type', 'product_id', 'deleted_at', 'currency'];
            if (in_array($key, $keys)) {
                return false;
            }

            return $value !== null && ! str_contains($key, 'setup') && $value >= 0;
        })->keys()->first();
    }

    public function toFiltredArray(): array
    {
        return collect($this->getAttributes())->filter(function ($value, $key) {
            $keys = ['id', 'related_id', 'related_type', 'product_id', 'deleted_at', 'currency'];
            if (in_array($key, $keys)) {
                return false;
            }

            return $value !== null;
        })->toArray();
    }

    public static function createFromPrice(int $id, string $type, string $billing, ?float $price = null, ?float $setup = null, ?float $onetime = null): void
    {
        $tmp = [];
        $tmp['related_id'] = $id;
        $tmp['related_type'] = $type;
        $tmp['currency'] = currency();
        $tmp['onetime'] = $onetime;
        $tmp[$billing] = $price;
        $tmp['setup_'.$billing] = $setup;
        $tmp['setup_onetime'] = $setup;
        $pricing = new self;
        $pricing->fill($tmp);
        $pricing->save();
    }

    public static function createFromArray(array $data, int $id, string $type = 'product'): void
    {
        $tmp = [];
        $tmp['related_id'] = $id;
        $tmp['related_type'] = $type;
        $tmp['currency'] = currency();
        foreach ($data['pricing'] as $recurring => $price) {
            $tmp[$recurring] = $price['price'];
            $tmp['setup_'.$recurring] = $price['setup'] ?? null;
        }
        $pricing = new self;
        $pricing->fill($tmp);
        $pricing->save();
    }

    public static function createOrUpdateFromArray(array $data, int $id, string $type = 'product'): void
    {
        $pricing = self::where('related_id', $id)->where('related_type', $type)->first();
        if ($pricing) {
            $pricing->updateFromArray($data, $type);
        } else {
            self::createFromArray($data, $id, $type);
        }
    }

    public function updateFromArray(array $data, string $type = 'product'): void
    {
        $tmp = [];
        $tmp['currency'] = currency();
        $tmp['related_type'] = $type;
        foreach ($data['pricing'] as $recurring => $price) {
            $tmp[$recurring] = $price['price'];
            $tmp['setup_'.$recurring] = $price['setup'] ?? null;
        }
        $this->fill($tmp);
        $this->save();
    }

    public static function createOrUpdateIfChanged(array $data, int $from_id, string $from_type, int $id, string $type): void
    {
        $pricing = self::where('related_id', $from_id)->where('related_type', $from_type)->first();
        $tmp = [];
        $only = $pricing->only(array_merge($pricing->getRecurringTypes(), array_map(function ($recurring) {
            return 'setup_'.$recurring;
        }, $pricing->getRecurringTypes())));
        foreach ($data['pricing'] as $recurring => $price) {
            $tmp[$recurring] = $price['price'] ?? null;
            $tmp['setup_'.$recurring] = $price['setup'] ?? null;
        }
        $difference = array_diff($tmp, $only);
        if (count($difference) > 0) {
            self::createOrUpdateFromArray($data, $id, $type);
            PricingService::forgot();
        }

    }
}
