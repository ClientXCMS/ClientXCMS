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

namespace App\Console\Commands\Invoice;

use App\Models\Billing\InvoiceItem;
use App\Models\Provisioning\Service;
use Illuminate\Console\Command;

class InvoiceDeliveryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:delivery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deliver pending invoices to customers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running services:delivery at '.now()->format('Y-m-d H:i:s'));

        $successful = $this->deliverManualServices();
        $successful = $this->deliverInvoiceItems() && $successful;

        return $successful ? self::SUCCESS : self::FAILURE;
    }

    private function deliverManualServices(): bool
    {
        $successful = true;
        $services = Service::getItemsByMetadata('must_created_manually', '1');
        $services->each(function (Service $service) use (&$successful) {
            try {
                if (! $service->isPending()) {
                    $this->info("Service {$service->id} is not pending, skipping delivery.");

                    return;
                }
                $result = $service->deliver();
                if ($result->success) {
                    $service->attachMetadata('must_created_manually', '0');
                    $this->info("Service {$service->id} delivered : ".$result->message);
                } else {
                    $successful = false;
                    $this->error("Service {$service->id} delivery failed Error : ".$result->message);
                }
            } catch (\Exception $e) {
                $successful = false;
                $this->error("Service {$service->id} delivery failed : ".$e->getMessage());
            }
        });

        return $successful;
    }

    private function deliverInvoiceItems(): bool
    {
        $successful = true;
        $items = InvoiceItem::findItemsMustDeliver();
        $items->each(function (InvoiceItem $item) use (&$successful) {
            try {
                // Double livraison pour les renouvellements du à l'event RenewServiceListerner qui peut avoir déjà livré le service et donc on attend 2 minutes avant de relivrer
                if ($item->type == 'renewal' && ($item->invoice && $item->invoice->paid_at != null && $item->invoice->paid_at->gt(now()->subMinutes(2)))) {
                    $this->info("Skipping invoice item {$item->id} as it is a renewal and the invoice is not yet paid or too recent.");

                    return;
                }
                if ($item->tryDeliver()) {
                    $this->info("Service delivered for invoice item {$item->id}");
                } else {
                    $successful = false;
                    $this->error("Service delivery failed for invoice item {$item->id} (item not supported)");
                }
            } catch (\Exception $e) {
                $successful = false;
                $this->error("Service delivery failed for invoice item {$item->id} : ".$e->getMessage());
            }
        });

        return $successful;
    }
}
