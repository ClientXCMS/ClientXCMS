<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add status fields to theme_menu_links table for controlling menu item visibility
     * and behavior without removing items from navigation.
     */
    public function up(): void
    {
        Schema::table('theme_menu_links', function (Blueprint $table) {
            // Status field: active, soon, maintenance, disabled
            $table->string('status')->default('active');

            // Custom message for status (translatable via Translatable trait)
            $table->string('status_message')->nullable();

            // Custom icon class for status display (e.g., bi bi-clock)
            $table->string('status_icon')->nullable();

            // Scheduled status: when the status becomes active
            $table->timestamp('status_starts_at')->nullable();

            // Scheduled status: when the status ends (reverts to active)
            $table->timestamp('status_ends_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('theme_menu_links', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'status_message',
                'status_icon',
                'status_starts_at',
                'status_ends_at',
            ]);
        });
    }
};
