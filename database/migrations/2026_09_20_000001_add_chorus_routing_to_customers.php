<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_public_entity')->default(false)->after('tax_subject_status');
            $table->string('chorus_service_code', 100)->nullable()->after('is_public_entity');
            $table->string('chorus_commitment_number', 100)->nullable()->after('chorus_service_code');
        });
    }

    public function down(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn([
            'is_public_entity', 'chorus_service_code', 'chorus_commitment_number',
        ]));
    }
};
