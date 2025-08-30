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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('password', 255);
            $table->string('email');
            $table->string('firstname');
            $table->string('lastname');
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('last_login')->nullable();
            $table->string('last_login_ip', 100)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('signature')->nullable();
            $table->boolean('dark_mode')->default(false);
            $table->string('locale')->default('fr_FR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
