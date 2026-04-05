<?php

namespace App\Billing\Items;

use App\Contracts\Billing\InvoiceItemInterface;
use App\Models\Billing\InvoiceItem;
use App\Models\Store\Product;
use App\Contracts\Store\ProductTypeInterface;
use App\Services\Billing\InvoiceService;
use App\Models\Provisioning\Service;

class ProductInvoiceItem implements InvoiceItemInterface
{
    public function uuid(): string
    {
        return 'product';
    }

    public function type(): string|array
    {
        return array_merge(ProductTypeInterface::ALL, ['product']);
    }

    public function relatedType(InvoiceItem $item): mixed
    {
        return Product::find($item->related_id);
    }

    public function tryDeliver(InvoiceItem $item): bool
    {
        // free_trial and service are handled similarly for delivery
        if ($item->type == 'service' || $item->type == 'free_trial') {
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
        }
        return false;
    }
}
