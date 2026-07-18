<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_sequences')) {
            Schema::create('invoice_sequences', function (Blueprint $t) {
                $t->id();
                // Prefix can vary (CTX, CTX-PROFORMA, …) so we keep it
                // out of the unique key to support multiple shapes.
                $t->string('prefix', 60);
                $t->string('year_month', 7); // "YYYY-MM"
                $t->unsignedInteger('last_number')->default(0);
                $t->timestamps();
                $t->unique(['prefix', 'year_month'], 'invoice_sequences_unique');
            });
        }

        if (Schema::hasTable('invoices') && ! Schema::hasColumn('invoices', 'pdf_sha256')) {
            Schema::table('invoices', function (Blueprint $t) {
                $t->string('pdf_sha256', 64)->nullable()->after('paid_at');
            });
        }

        if (! Schema::hasTable('credit_notes')) {
            Schema::create('credit_notes', function (Blueprint $t) {
                $t->id();
                $t->string('credit_note_number', 60)->unique();
                $t->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $t->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
                $t->string('reason', 255)->nullable();
                $t->decimal('amount', 14, 2);
                $t->decimal('tax', 14, 2)->default(0);
                $t->string('currency', 8);
                $t->string('pdf_sha256', 64)->nullable();
                $t->foreignId('issued_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $t->timestamps();
                $t->softDeletes();
                $t->index(['customer_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'pdf_sha256')) {
            Schema::table('invoices', function (Blueprint $t) {
                $t->dropColumn('pdf_sha256');
            });
        }
        Schema::dropIfExists('invoice_sequences');
    }
};
