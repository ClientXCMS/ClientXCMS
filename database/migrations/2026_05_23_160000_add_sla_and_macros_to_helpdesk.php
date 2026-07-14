<?php

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
    }

    public function down(): void
    {
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
