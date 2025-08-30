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
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->constrained('admins')->onDelete('set null');
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->enum('status_temp', ['open', 'closed', 'answered'])->default('open');
        });
        DB::statement('UPDATE support_tickets SET status_temp = status');

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->renameColumn('status_temp', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn('assigned_to');
        });
    }
};
