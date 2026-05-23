<?php

/*
 * v2.16 — Legal-compliance hardening of the billing flow.
 *
 *   1. invoice_sequences — atomic counter for invoice numbers.
 *      Previously Invoice::generateInvoiceNumber() did
 *      `Invoice::where(...)->count() + 1` which is a textbook race
 *      condition: two parallel paying customers can be allocated the
 *      same invoice_number, breaking the sequential numbering legally
 *      required in France (CGI art. 289) and most of the EU. This
 *      table stores one row per (prefix, year_month) with a
 *      `last_number` that we INCREMENT inside a transaction.
 *
 *   2. invoices.pdf_sha256 — SHA-256 of the generated PDF, computed
 *      once when the file is produced. Lets admins prove a posteriori
 *      that the PDF served to the customer has not been tampered with
 *      (auditor's question: "is this still the original?").
 *
 * Both additions are nullable / additive — every existing invoice
 * row keeps working without modification. Backfill happens lazily on
 * the next PDF (re)generation for hashes; existing invoice numbers
 * are preserved and the sequence table is bootstrapped from them on
 * first call to nextNumber().
 */

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
