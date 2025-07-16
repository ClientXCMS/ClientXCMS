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
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $action_log_id
 * @property string $attribute
 * @property string|null $old_value
 * @property string|null $new_value
 * @property-read \App\Models\ActionLog $actionLog
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLogEntries newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLogEntries newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLogEntries query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLogEntries whereActionLogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLogEntries whereAttribute($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLogEntries whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLogEntries whereNewValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLogEntries whereOldValue($value)
 * @mixin \Eloquent
 */
class ActionLogEntries extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'action_log_id',
        'attribute',
        'old_value',
        'new_value',
    ];

    public function actionLog()
    {
        return $this->belongsTo(ActionLog::class);
    }
}
