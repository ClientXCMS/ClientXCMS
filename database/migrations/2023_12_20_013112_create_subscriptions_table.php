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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('token')->unique();
            $table->unsignedBigInteger('gateway_id');
            $table->enum('state', ['active', 'pending', 'canceled', 'expired'])->default('active');
            $table->string('related_type');
            $table->integer('related_id');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('gateway_id')->references('id')->on('gateways');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->integer('amount')->default(0);
            $table->integer('setup_fee')->default(0);
            $table->string('currency')->default('USD');
            $table->integer('cycles')->default(1);
            $table->timestamps();
        });

        Schema::create('subscription_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('invoice_id');
            $table->boolean('paid')->default(0);
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->foreign('subscription_id')->references('id')->on('subscriptions');
            $table->foreign('invoice_id')->references('id')->on('invoices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
