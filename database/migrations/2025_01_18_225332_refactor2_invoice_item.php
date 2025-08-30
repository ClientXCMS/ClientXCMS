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
        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'parent_id')) {
                return;
            }
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('invoice_items')->onDelete('set null');
            $table->renameColumn('unit_price', 'unit_price_ht');
            $table->renameColumn('unit_setupfees', 'unit_setup_ht');
            $table->renameColumn('unit_original_price', 'unit_price_ttc');
            $table->renameColumn('unit_original_setupfees', 'unit_setup_ttc');
        });
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'price_ttc')) {
                return;
            }
            $table->float('price_ttc')->default(0);
        });
        foreach (\App\Models\Provisioning\Service::all() as $service) {
            $service->price_ttc = $service->price;
            $service->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
