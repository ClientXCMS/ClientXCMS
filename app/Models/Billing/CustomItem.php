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

use App\Core\NoneProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @OA\Schema (
 *     schema="CustomItem",
 *     title="Custom Item",
 *     description="Custom product added to an invoice manually",
 *     required={"name", "unit_price"},
 * 
 *     @OA\Property(property="id", type="integer", example=12),
 *     @OA\Property(property="name", type="string", example="Custom Service Fee"),
 *     @OA\Property(property="description", type="string", example="One-time service activation fee"),
 *     @OA\Property(property="unit_price", type="number", format="float", example=25.00),
 *     @OA\Property(property="unit_setupfees", type="number", format="float", example=5.00)
 * )
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float $unit_price
 * @property float $unit_setupfees
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem whereUnitSetupfees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomItem withoutTrashed()
 * @mixin \Eloquent
 */
class CustomItem extends Model
{
    use HasFactory, softDeletes;

    const CUSTOM_ITEM = 'custom_item';

    protected $fillable = [
        'name',
        'description',
    ];

    public function productType(): NoneProductType
    {
        return new NoneProductType;
    }
}
