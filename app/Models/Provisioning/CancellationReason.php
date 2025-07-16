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
namespace App\Models\Provisioning;

use App\Models\Traits\ModelStatutTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property string $reason
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CancellationReason withoutTrashed()
 * @mixin \Eloquent
 */
class CancellationReason extends Model
{
    use HasFactory, ModelStatutTrait, SoftDeletes;

    protected $fillable = [
        'reason',
        'status',
    ];

    public static function getReasons()
    {
        return \App\Models\Provisioning\CancellationReason::getAvailable(false)->pluck('reason', 'id');
    }

    public static function getCancellationMode()
    {
        return [
            'end_of_period' => __('client.services.cancel.expiration_end'),
            'now' => __('client.services.cancel.expiration_now'),
        ];
    }
}
