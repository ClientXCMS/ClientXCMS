<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration makes customer_id nullable on related tables
     * to allow account deletion while preserving historical data.
     */
    public function up(): void
    {
        // Make invoices.customer_id nullable
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        // Make support_tickets.customer_id nullable
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        // Make coupon_usages.customer_id nullable
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        // Make email_messages.recipient_id nullable if not already
        Schema::table('email_messages', function (Blueprint $table) {
            if (Schema::hasColumn('email_messages', 'recipient_id')) {
                $table->unsignedBigInteger('recipient_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert invoices.customer_id
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
            $table->foreign('customer_id')->references('id')->on('customers');
        });

        // Revert support_tickets.customer_id
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });

        // Revert coupon_usages.customer_id
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }
};
