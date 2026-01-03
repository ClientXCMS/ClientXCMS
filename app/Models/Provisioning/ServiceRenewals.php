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

use App\Models\Billing\Invoice;
use Database\Factories\Provisioning\ServiceRenewalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema (
 *     schema="ServiceRenewals",
 *     title="Service Renewals",
 *     description="Information about a service renewal cycle",
 *     required={"service_id", "invoice_id", "start_date", "end_date"},
 *
 *     @OA\Property(property="id", type="integer", example=87),
 *     @OA\Property(property="service_id", type="integer", example=1),
 *     @OA\Property(property="invoice_id", type="integer", example=1001),
 *     @OA\Property(property="start_date", type="string", format="date-time", example="2024-05-01T00:00:00Z"),
 *     @OA\Property(property="end_date", type="string", format="date-time", example="2024-06-01T00:00:00Z"),
 *     @OA\Property(property="renewed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="next_billing_on", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="period", type="string", example="monthly"),
 *     @OA\Property(property="first_period", type="boolean", example=true)
 * )
 *
 * @property int $id
 * @property int $service_id
 * @property int $invoice_id
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $renewed_at
 * @property \Illuminate\Support\Carbon|null $next_billing_on
 * @property int $period
 * @property int $first_period
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Invoice|null $invoice
 * @property-read \App\Models\Provisioning\Service|null $service
 *
 * @method static \Database\Factories\Provisioning\ServiceRenewalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals whereFirstPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals whereNextBillingOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals whereRenewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRenewals withoutTrashed()
 *
 * @mixin \Eloquent
 */
class ServiceRenewals extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_id',
        'invoice_id',
        'start_date',
        'end_date',
        'renewed_at',
        'next_billing_on',
        'period',
        'first_period',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'renewed_at' => 'datetime',
        'next_billing_on' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public static function findServiceByInvoiceItem(\App\Models\Billing\InvoiceItem $item)
    {
        return self::where('invoice_id', $item->invoice_id)
            ->first();
    }

    protected static function newFactory()
    {
        return ServiceRenewalFactory::new();
    }
}
