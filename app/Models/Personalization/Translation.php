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

namespace App\Models\Personalization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $model
 * @property int $model_id
 * @property string $key
 * @property string $locale
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'content',
        'locale',
        'model',
        'model_id',
    ];

    public static function getTranslation(string $model, int $model_id, string $key, string $locale, string $default = ''): string
    {
        $translation = self::where('model', $model)->where('model_id', $model_id)->where('key', $key)->where('locale', $locale)->first();
        if ($translation == null) {
            return $default;
        }

        return $translation->content;
    }
}
