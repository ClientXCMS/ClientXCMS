<?php

/*
 * This file is part of the CLIENTXCMS project.
 *
 * Adds a deterministic status column to `service_renewals` and a partial
 * uniqueness guarantee: a given service can have AT MOST ONE pending renewal
 * row at a time. The constraint is implemented via a generated column
 * (`pending_lock_key`) which is `NULL` for everything except pending rows,
 * combined with a UNIQUE index. NULL values in MySQL do not collide on
 * unique indexes, so paid / cancelled / soft-deleted rows never trip it.
 *
 * Backfill rules (additive, no destructive change):
 *   - renewed_at IS NOT NULL                  → status = 'paid'
 *   - deleted_at IS NOT NULL                  → status = 'cancelled'
 *   - linked invoice in (cancelled, refunded) → status = 'cancelled'
 *   - everything else                         → status = 'pending'
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_renewals')) {
            return;
        }

        // 1. Add the status column (additive, default keeps BC for any third-party insert).
        Schema::table('service_renewals', function (Blueprint $table) {
            if (! Schema::hasColumn('service_renewals', 'status')) {
                $table->string('status', 16)
                    ->default('pending')
                    ->after('first_period')
                    ->index();
            }
        });

        // 2. Backfill — done in raw SQL to remain fast and order-stable.
        DB::statement(<<<'SQL'
            UPDATE service_renewals sr
            LEFT JOIN invoices i ON i.id = sr.invoice_id
            SET sr.status = CASE
                WHEN sr.renewed_at IS NOT NULL THEN 'paid'
                WHEN sr.deleted_at IS NOT NULL THEN 'cancelled'
                WHEN i.status IN ('cancelled', 'refunded') THEN 'cancelled'
                ELSE 'pending'
            END
        SQL);

        // 3. Add the partial uniqueness guard via a generated column.
        //    MySQL >= 5.7 supports indexable VIRTUAL generated columns; we keep
        //    expression simple to maximise portability (MariaDB 10.4+).
        if (! Schema::hasColumn('service_renewals', 'pending_lock_key')) {
            DB::statement(<<<'SQL'
                ALTER TABLE service_renewals
                ADD COLUMN pending_lock_key BIGINT UNSIGNED
                AS (CASE WHEN status = 'pending' AND deleted_at IS NULL THEN service_id ELSE NULL END)
                VIRTUAL
            SQL);

            // Best-effort: if the schema is too dirty to add the constraint
            // (e.g. existing duplicate pending rows), keep the column but skip
            // the UNIQUE so the migration never blocks an upgrade.
            try {
                DB::statement('CREATE UNIQUE INDEX service_renewals_pending_lock_unique ON service_renewals (pending_lock_key)');
            } catch (\Throwable $e) {
                // Log the conflict so the admin can resolve duplicates and re-run later.
                logger()->warning('[v2.16] Could not enforce service_renewals_pending_lock_unique – existing duplicates detected. '.$e->getMessage());
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_renewals')) {
            return;
        }

        // Drop the unique index first (it depends on the generated column).
        try {
            DB::statement('DROP INDEX service_renewals_pending_lock_unique ON service_renewals');
        } catch (\Throwable $e) {
            // index may not exist if the upgrade fell back; safe to ignore.
        }

        if (Schema::hasColumn('service_renewals', 'pending_lock_key')) {
            DB::statement('ALTER TABLE service_renewals DROP COLUMN pending_lock_key');
        }

        if (Schema::hasColumn('service_renewals', 'status')) {
            Schema::table('service_renewals', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
