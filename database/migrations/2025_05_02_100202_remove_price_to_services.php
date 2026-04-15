<?php

use App\Models\Provisioning\ConfigOptionService;
use App\Models\Provisioning\Service;
use App\Models\Store\Pricing;
use App\Services\Store\PricingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'price')) {
            return;
        }
        DB::beginTransaction();
        $services = Service::whereNull('product_id')->get();
        if (Pricing::where('related_type', 'services')->count() == 0) {
            foreach ($services as $service) {
                Pricing::createFromPrice($service->id, 'services', $service->billing, $service->billing == 'onetime' ? 0 : $service->price, null, $service->billing == 'onetime' ? $service->price : 0);
            }
        }
        /** @var Service[] $services */
        $services = Service::whereNotNull('product_id')->get();
        if ($services->isNotEmpty() && Pricing::where('related_type', 'services')->count() == 0) {
            foreach ($services as $service) {
                if ($service->product == null) {
                    Pricing::createFromPrice($service->id, 'services', $service->billing, $service->billing == 'onetime' ? 0 : $service->price, null, $service->billing == 'onetime' ? $service->price : null);

                    continue;
                }
                if ($service->price != $service->product->getPriceByCurrency($service->currency, $service->billing)->price) {
                    Pricing::createFromPrice($service->id, 'services', $service->billing, $service->billing == 'onetime' ? 0 : $service->price, null, $service->billing == 'onetime' ? $service->price : null);
                }
            }
        }

        $configOptions = ConfigOptionService::all();
        if ($configOptions->isNotEmpty()) {
            /** @var ConfigOptionService $configOption */
            foreach ($configOptions as $configOption) {
                Pricing::createFromPrice($configOption->id, 'config_options_service', $configOption->service->billing, $configOption->recurring_amount, $configOption->setup_amount, $configOption->onetime_amount);
            }
        }
        PricingService::forgot();
        DB::commit();
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('price_ttc');
            $table->dropColumn('initial_price');
            $table->uuid();
        });
        Schema::table('config_options_services', function (Blueprint $table) {
            $table->dropColumn(['recurring_amount', 'onetime_amount', 'setup_amount']);
        });
        $services = Service::all();
        foreach ($services as $service) {
            $service->update(['uuid' => \Ramsey\Uuid\Uuid::uuid4()]);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            //
        });
    }
};
