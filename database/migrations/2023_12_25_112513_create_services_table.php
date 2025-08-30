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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->uuid('uuid');
            $table->string('name', 255);
            $table->string('type');
            $table->string('billing')->default('monthly');
            $table->string('currency')->default('USD');
            $table->unsignedBigInteger('server_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'expired', 'cancelled']);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspend_reason', 255)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->text('delivery_errors')->nullable();
            $table->unsignedInteger('delivery_attempts')->default(0);
            $table->unsignedInteger('renewals')->default(0);
            $table->timestamp('trial_ends_at')->nullable();
            $table->unsignedInteger('max_renewals')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->json('data')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
