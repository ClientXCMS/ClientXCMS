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

use App\Models\Traits\HasMetadata;
use App\Models\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $domain
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubdomainHost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubdomainHost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubdomainHost onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubdomainHost query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubdomainHost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubdomainHost whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubdomainHost whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubdomainHost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubdomainHost whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubdomainHost withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubdomainHost withoutTrashed()
 *
 * @mixin \Eloquent
 */
class SubdomainHost extends Model
{
    use HasFactory, HasMetadata, Loggable, softDeletes;

    protected $fillable = ['domain'];

    protected $table = 'subdomains_hosts';

    public function getDomainAttribute($value)
    {
        if ($value == null) {
            return null;
        }
        if ($value[0] != '.') {
            return '.'.$value;
        }

        return $value;
    }
}
