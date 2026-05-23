<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('badge_title')->nullable()->after('description');
            $table->string('badge_color', 32)->nullable()->after('badge_title');
            $table->string('badge_icon', 64)->nullable()->after('badge_color');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['badge_title', 'badge_color', 'badge_icon']);
        });
    }
};
