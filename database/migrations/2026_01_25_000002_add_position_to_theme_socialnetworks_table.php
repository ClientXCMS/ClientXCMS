<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theme_socialnetworks', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('theme_socialnetworks', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
