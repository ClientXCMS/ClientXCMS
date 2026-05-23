<?php

/*
 * v2.16 — Adds the avatar_path column to customers and admins so each
 * account can ship a profile photo.
 *
 * The column is varchar(255) NULL and stores a path **relative to the
 * configured public disk** (Storage::disk('public')->url($path) yields
 * the publicly served URL). NULL means "no photo, use the initials
 * fallback" — see the x-avatar Blade component.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['customers', 'admins'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (! Schema::hasColumn($table, 'avatar_path')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('avatar_path', 255)->nullable()->after('email');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['customers', 'admins'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'avatar_path')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('avatar_path');
                });
            }
        }
    }
};
