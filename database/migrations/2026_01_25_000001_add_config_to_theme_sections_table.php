<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theme_sections', function (Blueprint $table) {
            $table->json('config')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('theme_sections', function (Blueprint $table) {
            $table->dropColumn('config');
        });
    }
};
