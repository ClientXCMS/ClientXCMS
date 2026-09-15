<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_tlds', function (Blueprint $table) {
            $table->json('default_nameserver_ips')->nullable()->after('default_nameservers');
        });
    }

    public function down(): void
    {
        Schema::table('domain_tlds', fn (Blueprint $table) => $table->dropColumn('default_nameserver_ips'));
    }
};
