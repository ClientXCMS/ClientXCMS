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
        Schema::create('baskets', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('user_id')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('baskets_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('basket_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->text('options')->nullable();
            $table->integer('quantity')->default(1);
            $table->text('data')->nullable();
            $table->string('billing')->default('monthly');
            $table->string('currency')->default('eur');
            $table->timestamps();
            $table->foreign('basket_id')->references('id')->on('baskets')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baskets');
        Schema::dropIfExists('baskets_rows');
    }
};
