<?php

/*
 * v2.16 — Usage-based ("pay-as-you-go") billing.
 *
 * Two new tables:
 *
 *   service_usage_metrics
 *   ---------------------
 *   Append-only time series of usage samples. Extension modules
 *   (Proxmox, Pterodactyl, custom hosting nodes…) push samples via
 *   the {@see App\Services\Billing\UsageBillingService::recordMetric}
 *   API. At the end of the billing period the aggregator extracts the
 *   peak per metric_key for each service.
 *
 *   product_metered_rates
 *   ---------------------
 *   Tariff grid declared on a Product. Each row is one billable
 *   resource (e.g. metric_key='cpu_cores', unit_price=0.50,
 *   included_quantity=2 — "2 cores included, then 0.50 € per extra").
 *
 * Additive schema — no existing column changed. Removing the
 * migration leaves the historical "recurring" pricing flow untouched.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_usage_metrics')) {
            Schema::create('service_usage_metrics', function (Blueprint $t) {
                $t->id();
                $t->foreignId('service_id')->constrained('services')->cascadeOnDelete();
                $t->string('metric_key', 64); // e.g. "cpu_cores", "ram_gb", "storage_gb"
                $t->decimal('value', 14, 4);
                $t->timestamp('captured_at')->useCurrent();
                $t->timestamps();
                $t->index(['service_id', 'metric_key', 'captured_at'], 'usage_metrics_lookup');
            });
        }

        if (! Schema::hasTable('product_metered_rates')) {
            Schema::create('product_metered_rates', function (Blueprint $t) {
                $t->id();
                $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $t->string('metric_key', 64);
                $t->string('label', 120);  // human-friendly, e.g. "CPU core (vCPU)"
                $t->string('unit', 32)->nullable(); // e.g. "core", "GB", "hour"
                $t->decimal('unit_price', 14, 4)->default(0);
                $t->decimal('included_quantity', 14, 4)->default(0);
                $t->string('currency', 8)->default('EUR');
                $t->timestamps();
                $t->unique(['product_id', 'metric_key'], 'product_metered_rate_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_metered_rates');
        Schema::dropIfExists('service_usage_metrics');
    }
};
