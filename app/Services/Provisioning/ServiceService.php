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
 * Year: 2025
 */
namespace App\Services\Provisioning;

use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\DTO\Store\UpgradeDTO;
use App\Exceptions\WrongPaymentException;
use App\Models\Billing\Invoice;
use App\Models\Provisioning\CancellationReason;
use App\Models\Provisioning\Service;
use App\Models\Store\Product;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\Request;

class ServiceService
{
    public static function changeServiceStatus(Request $request, Service $service, string $status): array
    {
        if ($status == 'unsuspend') {
            $result = $service->unsuspend();
        } elseif ($status == 'suspend') {
            $result = $service->suspend(! empty($request->get('reason')) ? $request->get('reason') : 'No reason provided', $request->has('notify'));
        } elseif ($status == 'expire') {
            $result = $service->expire(true);
            $status = 'terminate';
        } elseif ($status == 'cancel') {
            if ($service->cancelled_at != null) {
                $result = $service->uncancel();
                $status = 'uncancel';
            } else {
                $reason = CancellationReason::find($request->get('reason'))->reason ?? 'No reason provided';
                $date = $request->expiration == 'end_of_period' ? $service->expires_at : new \DateTime;
                $result = $service->cancel($reason, $date, $request->get('expiration') == 'now');
            }
        } else {
            $result = new ServiceStateChangeDTO($service, false, 'Invalid status');
        }

        return [
            $result, $status,
        ];
    }

    /**
     * @param  Service  $service  - service to create invoice for
     * @param  string  $billing  - billing type
     * @param  string  $mode  - InvoiceService::CREATE_INVOICE or InvoiceService::APPEND_SERVICE
     * @param  int|null  $invoice_id  - append to existing invoice
     *
     * @throws \WrongPaymentException
     */
    public static function createRenewalInvoice(Service $service, string $billing, string $mode = InvoiceService::CREATE_INVOICE, ?int $invoice_id = null): Invoice
    {
        if ($service->invoice_id != null) {
            $invoice = $service->invoice;
            $existing = $invoice->items()->where('type', 'renewal')->where('related_id', $service->id)->first();
            if ($existing != null && ($existing->data['billing'] ?? '') != $service->billing) {
                $invoice->cancel();
                $service->update(['invoice_id' => null]);

                return self::createRenewalInvoice($service, $billing, $mode);
            }
            if ($existing != null) {
                return $invoice;
            }
        }
        if ($service->billing == 'onetime') {
            throw new WrongPaymentException('Cannot create invoice for onetime billing');
        }
        if ($mode == InvoiceService::CREATE_INVOICE) {
            $invoice = InvoiceService::createInvoiceFromService($service, $billing);
            $service->update(['invoice_id' => $invoice->id]);

            return $invoice;
        } elseif ($mode == InvoiceService::APPEND_SERVICE) {
            $invoice = Invoice::find($invoice_id);
            if ($invoice == null) {
                throw new WrongPaymentException('Invoice not found');
            }
            InvoiceService::appendServiceOnExistingInvoice($service, $invoice, $billing);

            return $invoice;
        } else {
            throw new \WrongPaymentException('Invalid mode for invoice creation');
        }

    }

    /**
     * @return Service|Upgrade
     *
     * @throws \WrongPaymentException
     */
    public static function upgradeService(Service $service, Product $product, string $type)
    {
        if ($type == UpgradeDTO::MODE_INVOICE) {
            $invoice = InvoiceService::createInvoiceFromUpgrade($service, $product);

            return $invoice;
        } elseif ($type == UpgradeDTO::MODE_NO_INVOICE) {
            $dto = new UpgradeDTO($service);
            $upgrade = $dto->createUpgrade($product);
            $upgrade->deliver();

            return $upgrade;
        } else {
            throw new \WrongPaymentException('Invalid upgrade type');
        }
    }
}
