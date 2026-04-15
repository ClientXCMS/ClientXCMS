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

use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Events\Core\Service\ServiceUpgraded;
use App\Models\Account\Customer;
use App\Models\Provisioning\Service;
use App\Models\Store\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema (
 *     schema="Upgrade",
 *     title="Upgrade",
 *     description="An upgrade operation applied to a service",
 *     required={"customer_id", "service_id", "old_product_id", "new_product_id"},
 *
 *     @OA\Property(property="id", type="integer", example=42),
 *     @OA\Property(property="customer_id", type="integer", example=1),
 *     @OA\Property(property="service_id", type="integer", example=2),
 *     @OA\Property(property="old_product_id", type="integer", example=5),
 *     @OA\Property(property="new_product_id", type="integer", example=6),
 *     @OA\Property(property="invoice_id", type="integer", example=1001),
 *     @OA\Property(property="upgraded", type="boolean", example=true)
 * )
 *
 * @property int $id
 * @property int $customer_id
 * @property int $service_id
 * @property int $old_product_id
 * @property int $new_product_id
 * @property int|null $invoice_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $upgraded
 * @property-read Customer $customer
 * @property-read \App\Models\Billing\Invoice|null $invoice
 * @property-read Product $newProduct
 * @property-read Product $oldProduct
 * @property-read Service $service
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade whereNewProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade whereOldProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Upgrade whereUpgraded($value)
 *
 * @mixin \Eloquent
 */
class Upgrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'service_id',
        'old_product_id',
        'new_product_id',
        'invoice_id',
        'upgraded',
    ];

    protected $casts = [
        'upgraded' => 'boolean',
    ];

    protected $attributes = [
        'upgraded' => false,
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function oldProduct()
    {
        return $this->belongsTo(Product::class, 'old_product_id');
    }

    public function newProduct()
    {
        return $this->belongsTo(Product::class, 'new_product_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function deliver(): ServiceStateChangeDTO
    {
        if ($this->upgraded) {
            return new ServiceStateChangeDTO($this->service, true, 'Service already upgraded');
        }
        try {
            if ($this->service->product == null) {
                throw new \Exception('No product found for service '.$this->service->id);
            }
            if ($this->service->server != null) {
                /** @var \App\Contracts\Provisioning\ServerTypeInterface $server */
                $server = $this->service->product->productType()->server();
                $result = $server->upgradeService($this->service, $this->newProduct);
            } else {
                $result = new ServiceStateChangeDTO($this->service, true, 'Auto upgrade service');
            }
            if ($result->success) {
                /** @var \App\DTO\Store\ProductPriceDTO $newPricing */
                $newPricing = $this->newProduct->getPriceByCurrency($this->service->currency, $this->service->billing);
                $service = $this->service;
                $service->product_id = $this->new_product_id;
                if ($service->name == $this->oldProduct->name) {
                    $service->name = $this->newProduct->name;
                }
                $service->save();
                $this->upgraded = true;
                $this->save();
                event(new ServiceUpgraded($this));
            } else {
                throw new \Exception($result->message);
            }

            return $result;
        } catch (\Exception $e) {
            return new ServiceStateChangeDTO($this->service, false, $e->getMessage());
        }
    }
}
