<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('cancellation_reason_id')->nullable()->after('cancelled_reason');
            $table->index('cancellation_reason_id');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['cancellation_reason_id']);
            $table->dropColumn('cancellation_reason_id');
        });
    }
};
