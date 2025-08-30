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


namespace App\Models\Admin;

use App\Models\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @OA\Schema (
 *     schema="Role",
 *     title="Role",
 *     description="Admin role and associated permissions",
 *     required={"name", "level"},
 * 
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Administrator"),
 *     @OA\Property(property="level", type="integer", example=100),
 *     @OA\Property(property="is_admin", type="boolean", description="Grants all permissions automatically", example=true),
 *     @OA\Property(property="is_default", type="boolean", description="Used as the default role when none is assigned", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-10T15:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-10T12:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         description="List of permissions attached to this role",
 * 
 *         @OA\Items(ref="#/components/schemas/Permission")
 *     )
 * )
 * @property int $id
 * @property string $name
 * @property int $level
 * @property bool $is_admin
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Admin\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Admin\Admin> $staffs
 * @property-read int|null $staffs_count
 * @method static \Database\Factories\Admin\RoleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutTrashed()
 * @mixin \Eloquent
 */
class Role extends Model
{
    use HasFactory, Loggable, softDeletes;

    protected $fillable = [
        'name',
        'level',
        'is_admin',
        'is_default',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function staffs()
    {
        return $this->hasMany(Admin::class);
    }

    public function hasPermission($permission)
    {
        if ($this->is_admin) {
            return true;
        }
        if ($permission == Permission::ALLOWED) {
            return true;
        }

        return $this->permissions->contains('name', $permission);
    }

    public function hasAnyPermission($permissions)
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->permissions->whereIn('name', $permissions)->isNotEmpty();
    }

    public function hasAllPermissions($permissions)
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->permissions->whereIn('name', $permissions)->count() == count($permissions);
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }

    public function isDefault()
    {
        return $this->is_default;
    }
}
