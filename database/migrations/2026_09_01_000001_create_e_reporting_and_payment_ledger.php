<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoice_items', 'operation_category')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->string('operation_category', 20)->nullable()->after('tax_exemption_reason');
            });
        }

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('reverses_id')->nullable()->constrained('payment_transactions')->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 18, 2);
            $table->char('currency', 3);
            $table->string('payment_method')->nullable();
            $table->string('external_id')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['occurred_at', 'type']);
        });

        Schema::create('e_reporting_periods', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('regime', 40);
            $table->string('provider', 50);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamp('due_at');
            $table->string('status', 30)->default('open');
            $table->string('idempotency_key', 64)->unique();
            $table->string('payload_sha256', 64)->nullable();
            $table->string('external_id')->nullable();
            $table->string('artifact_path')->nullable();
            $table->json('totals')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['type', 'provider', 'period_start', 'period_end'], 'er_period_type_provider_dates_uq');
        });

        Schema::create('e_reporting_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->nullable()->constrained('e_reporting_periods')->restrictOnDelete();
            $table->nullableMorphs('source');
            $table->string('type', 20);
            $table->date('fiscal_date');
            $table->string('customer_scope', 30);
            $table->char('country', 2)->nullable();
            $table->char('currency', 3);
            $table->decimal('vat_rate', 7, 4)->default(0);
            $table->string('tax_category', 20)->default('standard');
            $table->string('operation_category', 20)->nullable();
            $table->decimal('amount_ht', 18, 2);
            $table->decimal('amount_tax', 18, 2);
            $table->decimal('amount_ttc', 18, 2);
            $table->string('fingerprint', 64)->unique();
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->index(['type', 'fiscal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_reporting_entries');
        Schema::dropIfExists('e_reporting_periods');
        Schema::dropIfExists('payment_transactions');
        if (Schema::hasColumn('invoice_items', 'operation_category')) {
            Schema::table('invoice_items', fn (Blueprint $table) => $table->dropColumn('operation_category'));
        }
    }
};
