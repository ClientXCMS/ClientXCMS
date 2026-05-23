<?php

namespace Tests\Unit\Models\Admin;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * v2.16 — verifies the new role hierarchy + scoped-permission lookups.
 */
class RolePermissionsV2Test extends TestCase
{
    use RefreshDatabase;

    private function permission(string $name): Permission
    {
        return Permission::firstOrCreate(['name' => $name], ['label' => $name]);
    }

    public function test_role_with_direct_grant_passes(): void
    {
        $role = Role::create(['name' => 'Agent', 'level' => 1, 'is_admin' => false, 'is_default' => false]);
        $perm = $this->permission('admin.manage_tickets');
        $role->permissions()->attach($perm->id);
        $role->refresh()->load('permissions');

        $this->assertTrue($role->hasPermission('admin.manage_tickets'));
        $this->assertFalse($role->hasPermission('admin.manage_settings'));
    }

    public function test_role_inherits_permissions_from_parent(): void
    {
        $senior = Role::create(['name' => 'Senior', 'level' => 5, 'is_admin' => false, 'is_default' => false]);
        $perm = $this->permission('admin.manage_settings');
        $senior->permissions()->attach($perm->id);

        $junior = Role::create([
            'name' => 'Junior',
            'level' => 1,
            'is_admin' => false,
            'is_default' => false,
            'parent_role_id' => $senior->id,
        ]);

        $junior->load('permissions', 'parent.permissions');
        $this->assertTrue($junior->hasPermission('admin.manage_settings'));
        $this->assertContains('admin.manage_settings', $junior->effectivePermissionNames());
    }

    public function test_ancestor_chain_is_cycle_safe(): void
    {
        $a = Role::create(['name' => 'A', 'level' => 1, 'is_admin' => false, 'is_default' => false]);
        $b = Role::create(['name' => 'B', 'level' => 1, 'is_admin' => false, 'is_default' => false, 'parent_role_id' => $a->id]);
        // Cycle: a → b → a
        $a->parent_role_id = $b->id;
        $a->save();

        // No infinite loop, finite chain.
        $this->assertLessThanOrEqual(16, $b->ancestorChain()->count());
        $this->assertLessThanOrEqual(16, $a->ancestorChain()->count());
    }

    public function test_global_grant_satisfies_scoped_check(): void
    {
        $role = Role::create(['name' => 'Lead', 'level' => 1, 'is_admin' => false, 'is_default' => false]);
        $perm = $this->permission('admin.manage_tickets');
        $role->permissions()->attach($perm->id); // no scope

        $role->refresh()->load('permissions');
        $this->assertTrue($role->hasScopedPermission('admin.manage_tickets', 'department', 42));
    }

    public function test_scoped_grant_only_matches_same_scope(): void
    {
        $role = Role::create(['name' => 'DeptOwner', 'level' => 1, 'is_admin' => false, 'is_default' => false]);
        $perm = $this->permission('admin.manage_tickets');
        $role->permissions()->attach($perm->id, ['scope_type' => 'department', 'scope_id' => 7]);

        $role->refresh()->load('permissions');
        $this->assertTrue($role->hasScopedPermission('admin.manage_tickets', 'department', 7));
        $this->assertFalse($role->hasScopedPermission('admin.manage_tickets', 'department', 8));
        $this->assertFalse($role->hasScopedPermission('admin.manage_tickets', 'product', 7));
    }

    public function test_is_admin_short_circuits_everything(): void
    {
        $role = Role::create(['name' => 'God', 'level' => 99, 'is_admin' => true, 'is_default' => false]);
        $this->assertTrue($role->hasPermission('anything.you.want'));
        $this->assertTrue($role->hasScopedPermission('admin.manage_tickets', 'department', 1));
        $this->assertTrue($role->hasAllPermissions(['x', 'y']));
    }
}
