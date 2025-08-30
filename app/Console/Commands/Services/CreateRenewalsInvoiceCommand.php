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


namespace App\Console\Commands\Services;

use App\Models\Provisioning\Service;
use App\Services\Billing\InvoiceService;
use Illuminate\Console\Command;

class CreateRenewalsInvoiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:renewals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will create invoices for services that are due for renewal.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $services = Service::getShouldCreateInvoice();
        $this->info('Running services:renewals at '.now()->format('Y-m-d H:i:s'));
        foreach ($services as $service) {
            $invoice = InvoiceService::createInvoiceFromService($service, $service->billing);
            logger()->info("Created invoice for service #{$service->id}");
            $service->invoice_id = $invoice->id;
            $service->save();
            $this->info("Created invoice for service #{$service->id}");
        }
    }
}
