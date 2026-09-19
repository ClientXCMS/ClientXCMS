<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_exports', function (Blueprint $table) {
            $table->id();
            $table->morphs('exportable');
            $table->string('provider', 50);
            $table->string('event_type', 30);
            $table->string('status', 30);
            $table->string('idempotency_key', 64)->unique();
            $table->string('payload_sha256', 64)->nullable();
            $table->string('external_id')->nullable();
            $table->string('last_error_code')->nullable();
            $table->text('last_error_message')->nullable();
            $table->longText('response')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['provider', 'status'], 'accounting_exports_provider_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_exports');
    }
};
