<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cancellation_reasons', function (Blueprint $table) {
            $table->enum('cancellation_mode', ['immediate', 'support_ticket', 'after_expiration'])
                ->default('immediate')
                ->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('cancellation_reasons', function (Blueprint $table) {
            $table->dropColumn('cancellation_mode');
        });
    }
};

