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

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mode = setting('store_mode_tax', \App\Services\Store\TaxesService::MODE_TAX_INCLUDED);
        \App\Models\Admin\Setting::updateSettings([
            'store_mode_tax' => $mode === \App\Services\Store\TaxesService::MODE_TAX_INCLUDED ? \App\Services\Store\TaxesService::PRICE_TTC : \App\Services\Store\TaxesService::PRICE_HT,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
