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
        Schema::create('theme_sections', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('theme_uuid');
            $table->string('path');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('url')->default('/');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
