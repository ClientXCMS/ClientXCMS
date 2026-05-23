<?php

/*
 * v2.16 — Permission scopes + role hierarchy.
 *
 *   1. roles.parent_role_id — optional inheritance chain. A role
 *      inherits every permission of its ancestors so the operator
 *      can build hierarchies like "Senior support → Support agent →
 *      Read-only". Cycles are prevented at the application layer.
 *
 *   2. permission_role.scope_type / scope_id — optional restriction
 *      to a specific entity. Examples:
 *        - scope_type='department', scope_id=3 → permission only on
 *          helpdesk department #3.
 *        - scope_type='product', scope_id=12 → permission only on
 *          orders/services of product #12.
 *        - NULL/NULL → permission is global (current behaviour).
 *
 * Both additions are nullable so any existing pivot row keeps working
 * as a global permission.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles') && ! Schema::hasColumn('roles', 'parent_role_id')) {
            Schema::table('roles', function (Blueprint $t) {
                $t->unsignedBigInteger('parent_role_id')->nullable()->after('level')->index();
            });
        }

        // permission_role is the Eloquent-style pivot, but the project also
        // exposes a "permission_role" Model. Both target the same table.
        if (Schema::hasTable('permission_role')) {
            Schema::table('permission_role', function (Blueprint $t) {
                if (! Schema::hasColumn('permission_role', 'scope_type')) {
                    $t->string('scope_type', 32)->nullable()->after('permission_id');
                }
                if (! Schema::hasColumn('permission_role', 'scope_id')) {
                    $t->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
                }
            });
            // Composite index so the hot path "role has this perm with
            // this scope" stays fast even with thousands of rows.
            try {
                Schema::table('permission_role', function (Blueprint $t) {
                    $t->index(['role_id', 'permission_id', 'scope_type', 'scope_id'], 'permission_role_scope_lookup');
                });
            } catch (\Throwable $e) {
                // index may already exist; safe to ignore.
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permission_role')) {
            try {
                Schema::table('permission_role', function (Blueprint $t) {
                    $t->dropIndex('permission_role_scope_lookup');
                });
            } catch (\Throwable $e) { /* index may not exist */
            }
            Schema::table('permission_role', function (Blueprint $t) {
                if (Schema::hasColumn('permission_role', 'scope_id')) {
                    $t->dropColumn('scope_id');
                }
                if (Schema::hasColumn('permission_role', 'scope_type')) {
                    $t->dropColumn('scope_type');
                }
            });
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'parent_role_id')) {
            Schema::table('roles', function (Blueprint $t) {
                $t->dropColumn('parent_role_id');
            });
        }
    }
};
