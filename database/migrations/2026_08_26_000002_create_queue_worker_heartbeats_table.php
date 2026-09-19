<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_worker_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->string('connection');
            $table->string('queue');
            $table->uuid('token')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['connection', 'queue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_worker_heartbeats');
    }
};
