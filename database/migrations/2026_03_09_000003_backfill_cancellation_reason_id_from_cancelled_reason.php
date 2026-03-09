<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('services')
                ->whereNull('cancellation_reason_id')
                ->whereRaw("cancelled_reason REGEXP '^[0-9]+$'")
                ->update(['cancellation_reason_id' => DB::raw('CAST(cancelled_reason AS UNSIGNED)')]);

            $pairs = DB::table('services')
                ->select('services.id as service_id', 'cancellation_reasons.id as reason_id')
                ->join('cancellation_reasons', 'cancellation_reasons.reason', '=', 'services.cancelled_reason')
                ->whereNull('services.cancellation_reason_id')
                ->whereNotNull('services.cancelled_reason')
                ->get();

            foreach ($pairs as $pair) {
                DB::table('services')->where('id', $pair->service_id)->update(['cancellation_reason_id' => $pair->reason_id]);
            }
        });
    }

    public function down(): void
    {
        // Intentionally no-op: backfill only.
    }
};
