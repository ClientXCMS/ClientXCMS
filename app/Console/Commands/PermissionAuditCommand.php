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

namespace App\Console\Commands;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Console\Command;

class PermissionAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:audit
                            {--sync : Synchronize permissions from JSON to database}
                            {--roles : Show permissions by role}
                            {--unused : Show unused permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and manage system permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Permission Audit ===');
        $this->newLine();

        if ($this->option('sync')) {
            return $this->syncPermissions();
        }

        if ($this->option('roles')) {
            return $this->showPermissionsByRole();
        }

        if ($this->option('unused')) {
            return $this->showUnusedPermissions();
        }

        // Default: show overview
        return $this->showOverview();
    }

    private function showOverview(): int
    {
        $permissionsFile = resource_path('permissions.json');
        $definedPermissions = json_decode(file_get_contents($permissionsFile), true);
        $dbPermissions = Permission::count();
        $roles = Role::with('permissions')->get();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Permissions in JSON', count($definedPermissions)],
                ['Permissions in Database', $dbPermissions],
                ['Roles', $roles->count()],
            ]
        );

        $this->newLine();
        $this->info('Available commands:');
        $this->line('  --sync   : Synchronize permissions from JSON to database');
        $this->line('  --roles  : Show permissions assigned to each role');
        $this->line('  --unused : Show permissions that are not used by any role');

        return self::SUCCESS;
    }

    private function syncPermissions(): int
    {
        $this->info('Synchronizing permissions...');

        $permissionsFile = resource_path('permissions.json');
        if (! file_exists($permissionsFile)) {
            $this->error('permissions.json not found');

            return self::FAILURE;
        }

        $definedPermissions = json_decode(file_get_contents($permissionsFile), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON in permissions.json: '.json_last_error_msg());

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;

        foreach ($definedPermissions as $permissionData) {
            $permission = Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                [
                    'label' => $permissionData['label'],
                    'group' => $permissionData['group'] ?? null,
                ]
            );

            if ($permission->wasRecentlyCreated) {
                $created++;
                $this->line("✓ Created: {$permissionData['name']}");
            } else {
                $updated++;
            }
        }

        $this->newLine();
        $this->info('Synchronization complete!');
        $this->line("Created: {$created}");
        $this->line("Updated: {$updated}");
        $this->line('Total: '.count($definedPermissions));

        return self::SUCCESS;
    }

    private function showPermissionsByRole(): int
    {
        $roles = Role::with('permissions')->get();

        foreach ($roles as $role) {
            $this->newLine();
            $header = $role->name;
            if ($role->is_admin) {
                $header .= ' (ADMIN - All permissions)';
            }
            $this->info($header);
            $this->line(str_repeat('-', strlen($header)));

            if ($role->is_admin) {
                $this->line('Has access to ALL permissions');
            } elseif ($role->permissions->isEmpty()) {
                $this->warn('No permissions assigned');
            } else {
                foreach ($role->permissions->groupBy('group') as $group => $permissions) {
                    $this->line("\n  {$group}:");
                    foreach ($permissions as $permission) {
                        $this->line("    • {$permission->name}");
                    }
                }
            }
        }

        return self::SUCCESS;
    }

    private function showUnusedPermissions(): int
    {
        $this->info('Finding unused permissions...');

        // Get all permissions
        $allPermissions = Permission::all();

        // Get all roles with their permissions
        $roles = Role::where('is_admin', false)
            ->with('permissions')
            ->get();

        // Collect all used permission IDs
        $usedPermissionIds = collect();
        foreach ($roles as $role) {
            $usedPermissionIds = $usedPermissionIds->merge($role->permissions->pluck('id'));
        }
        $usedPermissionIds = $usedPermissionIds->unique();

        // Find unused permissions
        $unusedPermissions = $allPermissions->whereNotIn('id', $usedPermissionIds);

        if ($unusedPermissions->isEmpty()) {
            $this->info('All permissions are being used by at least one role.');

            return self::SUCCESS;
        }

        $this->warn("Found {$unusedPermissions->count()} unused permissions:");
        $this->newLine();

        foreach ($unusedPermissions->groupBy('group') as $group => $permissions) {
            $this->line("  {$group}:");
            foreach ($permissions as $permission) {
                $this->line("    • {$permission->name}");
            }
        }

        return self::SUCCESS;
    }
}
