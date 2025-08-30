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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('port');
            $table->text('username');
            $table->text('password');
            $table->string('type');
            $table->string('address');
            $table->string('hostname');
            $table->unsignedInteger('maxaccounts')->default(0);
            $table->enum('status', ['active', 'hidden', 'unreferenced'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
