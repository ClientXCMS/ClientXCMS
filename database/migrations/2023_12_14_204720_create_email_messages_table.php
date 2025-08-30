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
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->string('recipient');

            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('subject');
            $table->unsignedBigInteger('template');
            $table->text('content');
            $table->foreign('recipient_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('template')->references('id')->on('email_templates')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
