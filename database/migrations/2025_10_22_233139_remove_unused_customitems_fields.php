<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('custom_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
            $table->dropColumn('unit_setupfees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_items', function (Blueprint $table) {
            $table->float('unit_price')->default(0);
            $table->float('unit_setupfees')->default(0);
        });
    }
};
