<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// S2: retro-hash plain invitation tokens left in flight after the model
// switched to sha256-at-rest. Heuristic: anything not matching ^[0-9a-f]{64}$
// is plain (Str::random is base62). Worst-case false-positive: one URL dies, owner resends.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('customer_account_invitations')
            ->whereNull('accepted_at')
            ->orderBy('id')
            ->each(function ($row) {
                if (preg_match('/^[0-9a-f]{64}$/', $row->token) === 1) {
                    return; // already hashed (or astronomically unlikely collision)
                }

                DB::table('customer_account_invitations')
                    ->where('id', $row->id)
                    ->update(['token' => hash('sha256', $row->token)]);
            });
    }

    public function down(): void
    {
        // sha256 is one-way: down is a no-op, downgrade requires resending invitations.
    }
};
