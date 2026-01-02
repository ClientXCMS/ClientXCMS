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

use App\Abstracts\PaymentMethodSourceDTO;
use App\Contracts\Store\GatewayTypeInterface;
use App\DTO\Core\Gateway\GatewayUriDTO;
use App\Models\Traits\ModelStatutTrait;
use App\Services\Core\PaymentTypeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * @OA\Schema (
 *     schema="Gateway",
 *     title="Gateway",
 *     description="A payment gateway available for customer payments",
 *     required={"uuid", "name"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="uuid", type="string", example="stripe"),
 *     @OA\Property(property="name", type="string", example="Stripe"),
 *     @OA\Property(property="status", type="string", example="active"),
 *     @OA\Property(property="minimal_amount", type="number", format="float", example=1.00)
 * )
 *
 * @property int $id
 * @property string $name
 * @property string $status
 * @property string $uuid
 * @property string $minimal_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Billing\Invoice> $invoices
 * @property-read int|null $invoices_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway whereMinimalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gateway withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Gateway extends Model
{
    use HasFactory, ModelStatutTrait, softDeletes;

    protected $fillable = [
        'name',
        'status',
        'uuid',
        'minimal_amount',
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($gateway) {
            $gateway->invoices()->delete();
        });
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return GatewayTypeInterface
     */
    public function paymentType()
    {
        return app(PaymentTypeService::class)->get($this->uuid);
    }

    public function createPayment(Invoice $invoice, Request $request)
    {
        $dto = new GatewayUriDTO($this, $invoice);

        return $this->paymentType()->createPayment($invoice, $this, $request, $dto);
    }

    public function processPayment(Invoice $invoice, Request $request)
    {
        $dto = new GatewayUriDTO($this, $invoice);

        return $this->paymentType()->processPayment($invoice, $this, $request, $dto);
    }

    public function payInvoice(Invoice $invoice, PaymentMethodSourceDTO $sourceDTO)
    {
        return $this->paymentType()->payInvoice($invoice, $sourceDTO);
    }

    public function getAttribute($key)
    {
        return parent::getAttribute($key);
    }

    public function getGatewayName()
    {
        if ($this->uuid == 'balance' && auth()->user() && auth()->user()->balance != 0) {
            return $this->name.' ('.formatted_price(auth()->user()->balance, currency()).')';
        } else {
            return $this->name;
        }
    }

    public function minimalAmount()
    {
        return $this->paymentType()->minimalAmount();
    }

    public function getGatewayNameWithAmount()
    {
        return $this->name.' ('.formatted_price($this->amount, currency()).')';
    }
}
