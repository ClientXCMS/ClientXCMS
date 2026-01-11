<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2026
 */

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
        Schema::create('section_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')
                  ->constrained('theme_sections')
                  ->onDelete('cascade');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('locale', 10)->nullable();
            $table->timestamps();

            $table->unique(['section_id', 'key', 'locale'], 'section_settings_unique');
            $table->index(['section_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_settings');
    }
};
