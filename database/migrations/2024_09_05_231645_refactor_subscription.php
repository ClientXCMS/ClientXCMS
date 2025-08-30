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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::drop('subscriptions');
        Schema::drop('subscription_logs');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        Schema::create('subscriptions', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->unsignedBigInteger('customer_id')->nullable();
            $blueprint->string('state')->default('pending');
            $blueprint->timestamp('cancelled_at')->nullable();
            $blueprint->integer('cycles')->default(0);
            $blueprint->timestamp('last_payment_at')->nullable();
            $blueprint->unsignedBigInteger('service_id')->nullable();
            $blueprint->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $blueprint->string('payment_method_id')->nullable();
        });
        Schema::create('subscription_logs', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->unsignedBigInteger('subscription_id');
            $blueprint->unsignedBigInteger('invoice_id');
            $blueprint->boolean('paid')->default(0);
            $blueprint->timestamp('start_date');
            $blueprint->timestamp('end_date')->nullable();
            $blueprint->foreign('subscription_id')->references('id')->on('subscriptions');
            $blueprint->foreign('invoice_id')->references('id')->on('invoices');
            $blueprint->timestamp('paid_at')->nullable();
            $blueprint->dropColumn('paid');
            $blueprint->float('amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
