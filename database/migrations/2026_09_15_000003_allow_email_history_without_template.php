<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            $table->dropForeign(['template']);
        });

        Schema::table('email_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('template')->nullable()->change();
            $table->foreign('template')->references('id')->on('email_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Rows archived without a template have no equivalent in the old shape.
        DB::table('email_messages')->whereNull('template')->delete();

        Schema::table('email_messages', function (Blueprint $table) {
            $table->dropForeign(['template']);
        });

        Schema::table('email_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('template')->nullable(false)->change();
            $table->foreign('template')->references('id')->on('email_templates')->cascadeOnDelete();
        });
    }
};
