<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */


namespace App\Console\Commands;

use App\Core\License\LicenseCache;
use App\Models\Admin\Permission;
use App\Models\Billing\Invoice;
use App\Models\Helpdesk\SupportTicket;
use App\Models\Provisioning\ConfigOptionService;
use App\Models\Provisioning\Service;
use App\Models\Store\Pricing;
use App\Services\Core\LocaleService;
use App\Services\Store\PricingService;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Finder\Finder;

class OnUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:on-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'On update command for CLIENTXCMS.';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        DB::beginTransaction();

        $this->updatePermissions();
        $this->downloadLocales();
        $this->migrateServicesWithoutProduct();
        $this->migrateServicesWithProduct();
        $this->migrateConfigOptions();
        $this->cleanupSections();

        PricingService::forgot();
        DB::commit();

        $this->cleanupOldColumns();
        $this->changeUuidValue();
        $this->downloadExtensions();
        \Artisan::call('clientxcms:define-billing-address-to-invoices');
        $this->info('CLIENTXCMS is up to date.');
    }

    private function downloadExtensions()
    {
        try {
            $extensions = LicenseCache::get()?->getExtensions();
            if ($extensions == null){
                return;
            }
            foreach ($extensions as $extension => $details) {
                app('extension')->update($details['type'], $extension);
                $this->info("Extension {$extension} has been updated.");
            }
        } catch (\Exception $e) {
            $this->error("Failed to download extensions: {$e->getMessage()}");
            return;
        }
    }

    private function changeUuidValue(): void
    {
        $table = [Service::class, SupportTicket::class, Invoice::class];
        foreach ($table as $model) {
            $uuids = $model::pluck('uuid', 'id')->toArray();
            if (empty($uuids)) {
                $this->info("No UUIDs found in {$model}.");
                continue;
            }
            $first = reset($uuids);
            if (strlen($first) === 8) {
                $this->info("UUIDs in {$model} are already short. No changes needed.");
                continue;
            }
            $this->info("Updating UUIDs in {$model}...");
            foreach ($uuids as $uuid) {
                $shortUuid = substr($uuid, -8);
                if ($model::where('uuid', $shortUuid)->exists()) {
                    $this->error("UUID {$uuid} already exists in {$model}. Regenerating a new UUID.");
                    $shortUuid = substr(uniqid(), -5);
                }
                $model::where('uuid', $uuid)->update(['uuid' => $shortUuid]);
                $this->info("UUID for {$model} with ID {$uuid} has been updated to {$shortUuid}.");
            }
        }
    }

    private function updatePermissions(): void
    {
        $departments = \App\Models\Helpdesk\SupportDepartment::all();
        foreach ($departments as $department) {
            Permission::updateOrCreate([
                'name' => "admin.manage_tickets_department.{$department->id}",
                'group' => 'permissions.helpdesk',
                'label' => 'permissions.manage_tickets_department',
            ]);
        }
    }

    private function downloadLocales(): void
    {
        $locales = ["es_ES", "en_GB"];
        foreach ($locales as $locale) {
            LocaleService::downloadFiles($locale);
        }
    }

    private function migrateServicesWithoutProduct(): void
    {
        $services = Service::whereNull('product_id')->get();

        if ($services->isEmpty()) {
            $this->info('There are no services without product.');
            return;
        }

        if (Pricing::where('related_type', 'service')->count() > 0) {
            $this->info('Service migration (without product) is already up to date.');
            return;
        }

        foreach ($services as $service) {
            Pricing::createFromArray(
                ['pricing' => [$service->billing => ['price' => $service->price, 'setup' => 0]]],
                $service->id,
                'service'
            );
            $this->info("Service {$service->id} has been updated.");
        }
    }

    private function migrateServicesWithProduct(): void
    {
        $services = Service::whereNotNull('product_id')->get();

        if ($services->isEmpty()) {
            $this->info('There are no services with product.');
            return;
        }

        if (Pricing::where('related_type', 'service')->count() > 0) {
            $this->info('Service migration (with product) is already up to date.');
            return;
        }

        foreach ($services as $service) {
            if ($service->price != $service->getPriceByCurrency($service->currency, $service->billing)->price) {
                Pricing::createFromPrice(
                    $service->id,
                    'service',
                    $service->billing,
                    $service->price,
                    null,
                    null
                );
                $this->info("Service {$service->id} has been updated.");
            }
        }
    }

    private function migrateConfigOptions(): void
    {
        $configOptions = ConfigOptionService::all();

        if ($configOptions->isEmpty()) {
            $this->info('There are no config options.');
            return;
        }
        if (Pricing::where('related_type', 'config_options_service')->count() > 0) {
            $this->info('Config options migration is already up to date.');
            return;
        }

        foreach ($configOptions as $configOption) {
            Pricing::createFromPrice(
                $configOption->id,
                'config_options_service',
                $configOption->service->billing,
                $configOption->recurring_amount,
                $configOption->setup_amount,
                $configOption->onetime_amount
            );
            $this->info("Service options {$configOption->id} has been updated.");       
        }
    }

    private function cleanupOldColumns(): void
    {
        if (Schema::hasColumn('services', 'price')) {
            $this->info('Removing old price columns from services and config_options_services tables.');
            $this->removeOldPriceColumns();
        }
    }

    private function removeOldPriceColumns()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('price_ttc');
        });
        Schema::table('config_options_services', function (Blueprint $table) {
            $table->dropColumn(['recurring_amount', 'onetime_amount', 'setup_amount']);
        });
    }

    private function cleanupSections(): void
    {
        $paths = [
            base_path('resources/themes/**/views'),
        ];

        $finder = new Finder();
        $finder
            ->files()
            ->in($paths)
            ->name('*.php')
            ->path('#(?:^|/)sections(?:_copy)?(?:/|$)#i');
        $total = 0;
        $modified = 0;

        foreach ($finder as $file) {
            $total++;
            $realPath = $file->getRealPath();
            if ($realPath === false) {
                $this->warn("Invalid path for: {$file->getRelativePathname()}");
                continue;
            }
            $this->info("Updating section: {$file->getRelativePathname()}");
            $content = file_get_contents($realPath);
            [$changed, $content] = $this->stripLeadingPhpHeader($content);
            $this->info("Changed: {$changed}");
            file_put_contents($realPath, $content);
            $modified++;
            $this->info("Section {$file->getRelativePathname()} has been updated.");
        }
    }

    private function stripLeadingPhpHeader(string $content): array
    {
        $original = $content;
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }
        $new = preg_replace('/^\s*<\?php[\s\S]*?\?>\s*/', '', $content, 1);
        $changed = ($new !== null) && ($new !== $content);
        if ($changed && substr($original, 0, 3) === "\xEF\xBB\xBF") {
            $new = "\xEF\xBB\xBF" . $new;
        }
        return [$changed, $new ?? $content];
    }


}
