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
 *
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
 *
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
 *
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
        // v2.16 — optional parent for inheritance
        'parent_role_id',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class)
            ->withPivot('scope_type', 'scope_id');
    }

    public function staffs()
    {
        return $this->hasMany(Admin::class);
    }

    /**
     * v2.16 — optional parent role; permissions inherit transitively.
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_role_id');
    }

    /**
     * Walk the parent chain (cycle-safe, capped at 16 hops).
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public function ancestorChain(int $maxDepth = 16): \Illuminate\Support\Collection
    {
        $chain = collect();
        $seen = [];
        $current = $this->parent;
        while ($current !== null && $maxDepth-- > 0) {
            if (isset($seen[$current->id])) {
                break;
            }
            $seen[$current->id] = true;
            $chain->push($current);
            $current = $current->parent;
        }

        return $chain;
    }

    /**
     * v2.16 — returns every permission name this role can wield,
     * including the ones inherited from its ancestors.
     */
    public function effectivePermissionNames(): array
    {
        $names = $this->permissions->pluck('name')->all();
        foreach ($this->ancestorChain() as $ancestor) {
            $names = array_merge($names, $ancestor->permissions->pluck('name')->all());
        }

        return array_values(array_unique($names));
    }

    public function hasPermission($permission)
    {
        if ($this->is_admin) {
            return true;
        }
        if ($permission == Permission::ALLOWED) {
            return true;
        }

        if ($this->permissions->contains('name', $permission)) {
            return true;
        }

        // v2.16 — fall back to the parent chain.
        foreach ($this->ancestorChain() as $ancestor) {
            if ($ancestor->permissions->contains('name', $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * v2.16 — Scope-aware lookup. A scoped grant on the permission
     * (e.g. `admin.manage_tickets` with scope_type=department,
     * scope_id=3) matches only when the caller requests the same
     * scope. A NULL/NULL row always wins (global grant).
     */
    public function hasScopedPermission(string $permission, ?string $scopeType, ?int $scopeId): bool
    {
        if ($this->is_admin) {
            return true;
        }
        if ($permission === Permission::ALLOWED) {
            return true;
        }

        $matches = function ($permissions) use ($permission, $scopeType, $scopeId): bool {
            foreach ($permissions as $p) {
                if ($p->name !== $permission) {
                    continue;
                }
                $rowScopeType = $p->pivot->scope_type ?? null;
                $rowScopeId = $p->pivot->scope_id ?? null;
                if ($rowScopeType === null && $rowScopeId === null) {
                    return true; // global grant
                }
                if ($rowScopeType === $scopeType && (int) $rowScopeId === (int) $scopeId) {
                    return true;
                }
            }

            return false;
        };

        if ($matches($this->permissions)) {
            return true;
        }
        foreach ($this->ancestorChain() as $ancestor) {
            if ($matches($ancestor->permissions)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission($permissions)
    {
        if ($this->is_admin) {
            return true;
        }

        foreach ((array) $permissions as $name) {
            if ($this->hasPermission($name)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions($permissions)
    {
        if ($this->is_admin) {
            return true;
        }

        $permissions = (array) $permissions;
        foreach ($permissions as $name) {
            if (! $this->hasPermission($name)) {
                return false;
            }
        }

        return count($permissions) > 0;
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
