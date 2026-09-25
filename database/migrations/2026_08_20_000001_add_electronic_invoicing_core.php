<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'customer_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('customer_type', 20)->default('individual')->after('company_name');
                $table->boolean('fiscal_profile_completed')->default(false)->after('customer_type');
                $table->string('legal_name')->nullable()->after('customer_type');
                $table->string('siren', 9)->nullable()->after('legal_name');
                $table->string('siret', 14)->nullable()->after('siren');
                $table->string('vat_number', 32)->nullable()->after('siret');
                $table->string('tax_registration_number', 64)->nullable()->after('vat_number');
                $table->string('tax_subject_status', 30)->default('unknown')->after('customer_type');
                $table->string('rna_number', 10)->nullable()->after('tax_registration_number');
            });
        }

        if (! Schema::hasColumn('invoices', 'billing_snapshot')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->json('billing_snapshot')->nullable()->after('billing_address');
                $table->timestamp('issued_at')->nullable()->after('due_date');
            });
        }

        if (! Schema::hasColumn('invoice_items', 'vat_rate')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->decimal('vat_rate', 7, 4)->nullable()->after('unit_setup_ttc');
                $table->string('tax_category', 20)->default('standard')->after('vat_rate');
                $table->string('tax_exemption_reason')->nullable()->after('tax_category');
            });
        }

        // Preserve the stored values while making future calculations deterministic.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            foreach (['total', 'subtotal', 'tax', 'setupfees', 'fees', 'balance'] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    DB::statement("ALTER TABLE invoices MODIFY {$column} DECIMAL(18,2) NOT NULL DEFAULT 0");
                }
            }
            foreach (['unit_price_ht', 'unit_setup_ht', 'unit_price_ttc', 'unit_setup_ttc'] as $column) {
                if (Schema::hasColumn('invoice_items', $column)) {
                    DB::statement("ALTER TABLE invoice_items MODIFY {$column} DECIMAL(18,6) NOT NULL DEFAULT 0");
                }
            }
        }

        if (! Schema::hasTable('electronic_documents')) {
            Schema::create('electronic_documents', function (Blueprint $table) {
                $table->id();
                $table->nullableMorphs('documentable');
                $table->string('provider', 50);
                $table->string('provider_document_id')->nullable();
                $table->string('format', 30)->nullable();
                $table->string('status', 30)->default('pending');
                $table->string('idempotency_key', 64)->unique();
                $table->string('payload_sha256', 64);
                $table->string('structured_document_path')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->string('last_error_code')->nullable();
                $table->text('last_error_message')->nullable();
                $table->json('raw_response')->nullable();
                $table->timestamps();
                $table->unique(['provider', 'provider_document_id']);
                $table->index(['provider', 'status']);
            });
        }

        if (! Schema::hasTable('electronic_document_events')) {
            Schema::create('electronic_document_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('electronic_document_id')->constrained()->cascadeOnDelete();
                $table->string('provider_event_id')->nullable();
                $table->string('provider_status', 50);
                $table->string('internal_status', 30);
                $table->string('event_code')->nullable();
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamp('received_at');
                $table->timestamps();
                $table->unique(['electronic_document_id', 'provider_event_id'], 'ed_events_doc_provider_event_uq');
            });
        }
        if (Schema::hasTable('electronic_document_events') && ! collect(Schema::getIndexes('electronic_document_events'))->contains(fn (array $index) => $index['name'] === 'ed_events_doc_provider_event_uq')) {
            Schema::table('electronic_document_events', fn (Blueprint $table) => $table->unique(['electronic_document_id', 'provider_event_id'], 'ed_events_doc_provider_event_uq'));
        }

        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->dropForeign(['invoice_id']);
                $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
            });
        }
        DB::table('customers')
            ->whereNotNull('company_name')
            ->where('company_name', '<>', '')
            ->where(fn ($query) => $query->whereNull('legal_name')->orWhere('legal_name', ''))
            ->orderBy('id')
            ->chunkById(500, function ($customers) {
                foreach ($customers as $customer) {
                    $isFrench = strtoupper((string) $customer->country) === 'FR';
                    $isComplete = $isFrench
                        ? preg_match('/^\d{9}$/', (string) $customer->siren) === 1
                        : (trim((string) $customer->tax_registration_number) !== '' || trim((string) $customer->vat_number) !== '');
                    DB::table('customers')->where('id', $customer->id)->update([
                        'legal_name' => $customer->company_name,
                        'customer_type' => 'business',
                        'fiscal_profile_completed' => $isComplete,
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $table->dropForeign(['invoice_id']);
                $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            });
        }
        Schema::dropIfExists('electronic_document_events');
        Schema::dropIfExists('electronic_documents');
        Schema::table('invoice_items', fn (Blueprint $table) => $table->dropColumn(['vat_rate', 'tax_category', 'tax_exemption_reason']));
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn(['billing_snapshot', 'issued_at']));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn(['customer_type', 'fiscal_profile_completed', 'legal_name', 'siren', 'siret', 'vat_number', 'tax_registration_number']));
    }
};
