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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->date('due_date');
            $table->unsignedBigInteger('customer_id');
            $table->float('total'); // TOTAL = SUBTOTAL + TAX + SETUP FEES - DISCOUNT
            $table->float('subtotal'); // SUBTOTAL = SUM OF ALL INVOICE ITEMS TOTAL
            $table->float('tax'); // TAX = SUBTOTAL * TAX RATE
            $table->float('setupfees'); // SETUP FEES = SUM OF ALL INVOICE ITEMS SETUP FEES
            $table->string('currency');
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('external_id')->unique()->nullable();
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->string('notes');
            $table->string('paymethod')->default('balance');
            $table->float('fees')->unsigned()->default(0);
            $table->string('invoice_number')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('quantity');
            $table->json('discount')->nullable();
            $table->float('unit_price_ht');
            $table->float('unit_setup_ht');
            $table->float('unit_price_ttc');
            $table->float('unit_setup_ttc');
            $table->unsignedBigInteger('invoice_id');
            $table->string('type');
            $table->integer('related_id')->nullable();
            $table->json('data');
            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('invoice_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_items');
    }
};
