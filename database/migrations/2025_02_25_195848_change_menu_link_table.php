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
        Schema::table('theme_menu_links', function (Blueprint $table) {
            $table->enum('link_type', ['new_tab', 'link', 'dropdown'])->default('link');
            $table->unsignedInteger('parent_id')->nullable();
            // $table->foreign('parent_id')->references('id')->on('theme_menu_links')->onDelete('cascade');
            $table->enum('allowed_role', ['all', 'logged', 'staff', 'customer'])->default('all');
            $table->string('icon')->nullable();
            $table->string('badge')->nullable();
            $table->string('description')->nullable();
            $table->string('url')->nullable();
            $table->json('items')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
