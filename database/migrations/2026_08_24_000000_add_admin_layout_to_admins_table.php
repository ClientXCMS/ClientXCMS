<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('admin_layout')->default('horizontal')->after('dark_mode');
            $table->timestamp('admin_layout_prompted_at')->nullable()->after('admin_layout');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('admin_layout');
            $table->dropColumn('admin_layout_prompted_at');
        });
    }
};
