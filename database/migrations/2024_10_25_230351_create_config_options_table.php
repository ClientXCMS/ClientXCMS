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
        Schema::create('config_options', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('key');
            $table->string('name');
            $table->string('default_value')->nullable();
            $table->string('rules')->nullable();
            $table->integer('min_value')->nullable();
            $table->integer('max_value')->nullable();
            $table->integer('step')->nullable();
            $table->boolean('required')->default(false);
            $table->string('unit')->nullable();
            $table->boolean('automatic')->default(true);
            $table->boolean('hidden')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('config_options_products', function (Blueprint $table) {
            $table->unsignedBigInteger('config_option_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('sort_order')->default(0);
            $table->foreign('config_option_id')->references('id')->on('config_options')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->timestamps();

        });

        Schema::create('config_options_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('config_option_id');
            $table->string('friendly_name')->nullable();
            $table->string('value')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('hidden')->default(false);
            $table->foreign('config_option_id')->references('id')->on('config_options')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_options_options');
        Schema::dropIfExists('config_options_products');
        Schema::dropIfExists('config_options');
    }
};
