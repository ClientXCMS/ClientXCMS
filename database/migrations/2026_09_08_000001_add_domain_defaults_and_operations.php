<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_tlds', function (Blueprint $table) {
            $table->json('default_nameservers')->nullable();
            $table->json('default_dns_records')->nullable();
            $table->boolean('apply_default_dns')->default(false);
        });
        Schema::create('domain_operations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kind');
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('server_id')->nullable();
            $table->string('connection_key')->nullable()->index();
            $table->string('status')->default('pending');
            $table->unsignedInteger('progress')->default(0);
            $table->json('payload')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_operations');
        Schema::table('domain_tlds', fn (Blueprint $table) => $table->dropColumn(['default_nameservers', 'default_dns_records', 'apply_default_dns']));
    }
};
