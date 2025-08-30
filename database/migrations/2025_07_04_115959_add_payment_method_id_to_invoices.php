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
        $metadata = \App\Models\Metadata::where('key', 'used_payment_method')->get();
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_method_id')->nullable()->after('status');
            $table->float('balance')->default(0)->after('total');
        });
        foreach ($metadata as $meta) {
            $invoice = \App\Models\Billing\Invoice::find($meta->model_id);
            if ($invoice) {
                $invoice->payment_method_id = $meta->value;
                $invoice->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_method_id');
        });
    }
};
