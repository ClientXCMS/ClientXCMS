<?php

/*
 * v2.16 — Tickets without an account (anonymous senders) + inbound
 * email integration.
 *
 * 1. customer_id becomes nullable so a ticket can exist without a
 *    registered customer (anonymous submission, inbound email from an
 *    address that doesn't match any account).
 * 2. guest_email + guest_name capture the sender details when there is
 *    no customer.
 * 3. guest_token is a 32-char random URL slug; the anonymous sender
 *    uses it to consult / reply to their ticket without logging in.
 * 4. inbound_message_id stores the original RFC 822 Message-ID of the
 *    most recent inbound email — used to thread replies that quote the
 *    notification we send out.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            // customer_id was historically NOT NULL. We make it nullable
            // so a guest can open a ticket without an account.
            $table->unsignedBigInteger('customer_id')->nullable()->change();

            $table->string('guest_email')->nullable()->after('customer_id');
            $table->string('guest_name')->nullable()->after('guest_email');
            $table->string('guest_token', 64)->nullable()->unique()->after('guest_name');
            $table->string('inbound_message_id', 255)->nullable()->after('guest_token');
        });

        // Add the message-id column on messages too, so we can deduplicate
        // inbound emails (don't re-process the same Message-ID twice).
        Schema::table('support_messages', function (Blueprint $table) {
            $table->string('inbound_message_id', 255)->nullable()->after('id');
            $table->index('inbound_message_id', 'support_messages_inbound_msgid_idx');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['guest_email', 'guest_name', 'guest_token', 'inbound_message_id']);
            // We deliberately do NOT flip customer_id back to NOT NULL —
            // there may be guest rows that the consumer wants to keep.
        });
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropIndex('support_messages_inbound_msgid_idx');
            $table->dropColumn('inbound_message_id');
        });
    }
};
