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
        Schema::table('services', function (Blueprint $table) {
            $table->enum('status_temp', ['pending', 'active', 'suspended', 'expired', 'cancelled', 'hidden'])->default('pending');
        });
        DB::statement('UPDATE services SET status_temp = status');
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->renameColumn('status_temp', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            //
        });
    }
};
