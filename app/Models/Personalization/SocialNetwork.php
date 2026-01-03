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
 * @property string $icon
 * @property string $name
 * @property string $url
 * @property int $hidden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SocialNetwork whereUrl($value)
 *
 * @mixin \Eloquent
 */
class SocialNetwork extends Model
{
    protected $table = 'theme_socialnetworks';

    use HasFactory;

    protected $fillable = [
        'icon',
        'name',
        'url',
    ];

    public static function getSvgFromResource(string $name): string
    {
        return file_get_contents(resource_path("svg/socials/{$name}.svg"));
    }
}
