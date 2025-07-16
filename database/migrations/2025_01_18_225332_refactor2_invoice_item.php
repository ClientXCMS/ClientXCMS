<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
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
