<?php

/*
 * v2.16 — Helpdesk advanced features.
 *
 *   1. SLA tracking on tickets:
 *      - sla_first_response_minutes / sla_resolution_minutes on
 *        support_departments lets the operator declare per-department
 *        targets. NULL columns mean "no SLA" (current behaviour).
 *      - support_tickets gets first_response_due_at,
 *        resolution_due_at and first_response_at so we can highlight
 *        tickets at risk and stop the clock when staff replies.
 *
 *   2. support_macros — pre-baked staff replies (a.k.a. "canned
 *      responses"). Each macro can target a list of departments
 *      (JSON), so the operator can curate per-team libraries.
 *
 * All columns are nullable / additive — removing the migration leaves
 * existing tickets and departments untouched.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_departments')) {
            Schema::table('support_departments', function (Blueprint $t) {
                if (! Schema::hasColumn('support_departments', 'sla_first_response_minutes')) {
                    $t->unsignedInteger('sla_first_response_minutes')->nullable()->after('id');
                }
                if (! Schema::hasColumn('support_departments', 'sla_resolution_minutes')) {
                    $t->unsignedInteger('sla_resolution_minutes')->nullable()->after('sla_first_response_minutes');
                }
            });
        }

        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $t) {
                if (! Schema::hasColumn('support_tickets', 'first_response_due_at')) {
                    $t->timestamp('first_response_due_at')->nullable()->index();
                }
                if (! Schema::hasColumn('support_tickets', 'resolution_due_at')) {
                    $t->timestamp('resolution_due_at')->nullable()->index();
                }
                if (! Schema::hasColumn('support_tickets', 'first_response_at')) {
                    $t->timestamp('first_response_at')->nullable()->index();
                }
                if (! Schema::hasColumn('support_tickets', 'sla_breached_notified_at')) {
                    $t->timestamp('sla_breached_notified_at')->nullable();
                }
            });
        }

        if (! Schema::hasTable('support_macros')) {
            Schema::create('support_macros', function (Blueprint $t) {
                $t->id();
                $t->string('name', 120);
                $t->string('shortcut', 40)->nullable()->index(); // e.g. ":welcome"
                $t->text('content'); // markdown body, supports %fullname% %service_name% %ticket_id%
                $t->json('department_ids')->nullable(); // null = available everywhere
                $t->unsignedInteger('use_count')->default(0);
                $t->boolean('enabled')->default(true)->index();
                $t->foreignId('created_by_id')->nullable()->constrained('admins')->nullOnDelete();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('support_macros')) {
            Schema::dropIfExists('support_macros');
        }

        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $t) {
                foreach (['first_response_due_at', 'resolution_due_at', 'first_response_at', 'sla_breached_notified_at'] as $col) {
                    if (Schema::hasColumn('support_tickets', $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('support_departments')) {
            Schema::table('support_departments', function (Blueprint $t) {
                foreach (['sla_first_response_minutes', 'sla_resolution_minutes'] as $col) {
                    if (Schema::hasColumn('support_departments', $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }
};
