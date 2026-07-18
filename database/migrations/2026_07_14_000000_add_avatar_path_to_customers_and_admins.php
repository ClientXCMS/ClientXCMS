<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['customers', 'admins'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'avatar_path')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->string('avatar_path')->nullable()->after('email'));
            }
        }
    }

    public function down(): void
    {
        foreach (['customers', 'admins'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'avatar_path')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('avatar_path'));
            }
        }
    }
};
