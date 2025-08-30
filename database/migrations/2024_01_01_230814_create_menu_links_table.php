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
        try {
            Schema::create('theme_menu_links', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('position');
                $table->enum('type', ['bottom', 'list', 'front']);
                $table->json('items');
                $table->timestamps();
            });
        } catch (Exception $e) {
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
