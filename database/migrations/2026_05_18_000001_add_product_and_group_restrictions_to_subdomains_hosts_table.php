<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subdomains_hosts', function (Blueprint $table) {
            $table->json('products')->nullable()->after('domain');
            $table->json('groups')->nullable()->after('products');
        });
    }

    public function down(): void
    {
        Schema::table('subdomains_hosts', function (Blueprint $table) {
            $table->dropColumn(['products', 'groups']);
        });
    }
};
