<?php

namespace App\Billing\Items;

use App\Contracts\Billing\InvoiceItemInterface;
use App\Models\Billing\InvoiceItem;
use App\Addons\Freetrial\DTO\FreetrialDTO;
use App\Services\Billing\InvoiceService;
use App\Models\Provisioning\Service;

class FreeTrialInvoiceItem implements InvoiceItemInterface
{
    public function uuid(): string
    {
        return 'free_trial';
    }

    public function type(): string|array
    {
        return 'free_trial'; // Type returned in FreetrialDTO is statically 'free_trial', or similar
    }

    public function relatedType(InvoiceItem $item): mixed
    {
        if (class_exists(FreetrialDTO::class)) {
            return new FreetrialDTO($item->related_id, $item);
        }

        return null;
    }

    public function tryDeliver(InvoiceItem $item): bool
    {
        $services = $item->getMetadata('services');
        if ($services == null) {
            try {
                InvoiceService::createServicesFromInvoiceItem($item->invoice, $item);
                $services = $item->getMetadata('services');
            } catch (\Exception $e) {
                throw new \Exception("Error creating services for invoice item {$item->id} : " . $e->getMessage());
            }
        }

        $delivered = [];
        $servicesArray = explode(',', $services);
        foreach ($servicesArray as $serviceId) {
            $service = Service::find($serviceId);
            if ($service == null) {
                $filteredServices = collect($servicesArray)->filter(fn($id) => $id != $serviceId)->implode(',');
                if (empty($filteredServices)) {
                    $item->detachMetadata('services');
                } else {
                    $item->attachMetadata('services', $filteredServices);
                }
                throw new \Exception("Service {$serviceId} not found for invoice item {$item->id}");
            }
            if ($service->status == 'active') {
                $delivered[] = $service->id;
                continue;
            }
            $result = $service->deliver();
            if ($result->success) {
                $delivered[] = $service->id;
            } else {
                throw new \Exception("Service {$service->id} delivery failed Error : " . $service->delivery_errors);
            }
        }

        if (count($delivered) == count($servicesArray)) {
            $item->delivered_at = now();
            $item->save();

            return true;
        }

        return false;
    }
}
