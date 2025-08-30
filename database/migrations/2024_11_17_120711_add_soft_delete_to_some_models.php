<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'products',
        'groups',
        'services',
        'invoices',
        'roles',
        'support_departments',
        'support_tickets',
        'support_messages',
        'support_attachments',
        'coupons',
        'subscriptions',
        'servers',
        'theme_sections',
        'cancellation_reasons',
        'subdomains_hosts',
        'pricings',
        'gateways',
        'custom_items',
        'admins',
        'invoice_items',
        'service_renewals',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = $this->tables;

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'deleted_at')) {
                    return;
                }
                $table->softDeletes();
            });
        }

        \App\Models\Metadata::where('key', 'disabled_many_services')->update(['key' => 'allow_only_as_much_services']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = $this->tables;

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
