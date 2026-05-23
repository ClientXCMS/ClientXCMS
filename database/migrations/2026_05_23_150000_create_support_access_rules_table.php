<?php

/*
 * v2.16 — Helpdesk ticket-access rules.
 *
 * One row = one configurable predicate evaluated when a customer tries
 * to open a ticket. Rules are matched in `priority` order (ASC). The
 * first rule that does NOT pass blocks the submission and surfaces its
 * `message_key` to the user.
 *
 * Schema is intentionally additive — rules live in their own table and
 * the SubmitTicketRequest only gains a new validation layer. Removing
 * this migration leaves every existing ticket and submission flow
 * untouched.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_access_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->integer('priority')->default(100)->index();

            // Scope — when set, the rule only applies to tickets that
            // match these filters. All filters AND together; a NULL
            // means "any".
            $table->unsignedBigInteger('scope_department_id')->nullable()->index();
            $table->unsignedBigInteger('scope_product_id')->nullable()->index();

            // Predicates evaluated against the draft ticket + customer.
            // Stored as JSON to keep the schema stable; the model
            // exposes typed accessors.
            //
            // Recognised keys (all optional):
            //   require_service_status: array<active|pending|suspended>
            //   require_option_uuid:    string (config option uuid that
            //                            must be attached to the linked service)
            //   require_active_service: bool
            //   block_priorities:       array<low|medium|high>
            //   require_related_type:   "service" | "invoice"
            $table->json('predicates');

            // Translation key shown when this rule blocks the submit.
            // The key is resolved with __() at render time; falls back
            // to a generic "access denied" message if missing.
            $table->string('message_key', 200)->default('v216::helpdesk.access.default_denied');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_access_rules');
    }
};
