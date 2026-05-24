<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S2 of the v2.16 audit - retro-hash plain invitation tokens in flight.
 *
 * The CustomerAccountInvitation model used to persist Str::random(64) as
 * the token. Now we only persist sha256(plain). Rows written before this
 * deploy hold the plain value; this migration rewrites each such row to
 * sha256(value) so the same outstanding invitation URLs keep working.
 *
 * Detection heuristic: sha256 hex is exactly 64 chars matching ^[0-9a-f]$.
 * Str::random uses base62 (a-zA-Z0-9), so any token containing a char
 * outside the hex alphabet is provably a plain. Tokens that happen to be
 * 64 hex chars - probability negligible for 64 random base62 chars - are
 * left alone (already considered hashed). Worst-case false-positive: one
 * invitation URL stops working, the owner can resend.
 */
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
        // sha256 is one-way - we cannot recover the original plain tokens.
        // Down is a no-op; downgrading this audit fix requires invalidating
        // pending invitations and asking owners to resend.
    }
};
