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
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('gdpr_compliment')->default(false);
            $table->string('company_name')->nullable()->after('email');
            $table->string('billing_details')->nullable()->after('company_name');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('billing_address')->nullable()->after('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('gdpr_compliment');
            $table->dropColumn('company_name');
            $table->dropColumn('billing_details');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('billing_address');
        });
    }
};
