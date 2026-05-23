<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_tlds', function (Blueprint $table) {
            $table->id();
            $table->string('extension')->unique();
            $table->enum('status', ['active', 'hidden', 'unreferenced'])->default('active');
            $table->foreignId('server_id')->nullable()->constrained('servers')->nullOnDelete();
            $table->boolean('dns_management')->default(true);
            $table->boolean('whois_privacy')->default(false);
            $table->timestamps();
        });

        Schema::create('domain_tld_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_tld_id')->constrained('domain_tlds')->cascadeOnDelete();
            $table->string('currency', 10);
            $table->string('action')->default('register');
            $table->string('billing')->default('annually');
            $table->float('price')->default(0);
            $table->float('setup')->default(0);
            $table->unique(['domain_tld_id', 'currency', 'action', 'billing'], 'domain_tld_price_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_tld_prices');
        Schema::dropIfExists('domain_tlds');
    }
};
