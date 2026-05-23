<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * v2.16 — One billable resource declared on a Product for
 * pay-as-you-go pricing.
 *
 * @property int $id
 * @property int $product_id
 * @property string $metric_key
 * @property string $label
 * @property string|null $unit
 * @property float $unit_price
 * @property float $included_quantity
 * @property string $currency
 */
class ProductMeteredRate extends Model
{
    use HasFactory;

    protected $table = 'product_metered_rates';

    protected $fillable = [
        'product_id',
        'metric_key',
        'label',
        'unit',
        'unit_price',
        'included_quantity',
        'currency',
    ];

    protected $casts = [
        'unit_price' => 'float',
        'included_quantity' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Compute the chargeable amount for an observed peak.
     */
    public function chargeFor(float $peak): float
    {
        $billable = max(0.0, $peak - $this->included_quantity);

        return round($billable * $this->unit_price, 4);
    }
}
